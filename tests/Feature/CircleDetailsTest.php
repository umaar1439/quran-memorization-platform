<?php

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function seedRolesForCircleDetails(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('org-admin can view the executive dashboard of their own circle', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForCircleDetails($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher->id,
        'name' => 'حلقة الفجر',
    ]);

    $studentUser = User::factory()->create(['organization_id' => $org->id]);
    $studentUser->assignRole($roles['student']);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'user_id' => $studentUser->id,
    ]);

    Recitation::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'type' => 'new',
        'surah_number' => 2,
        'ayah_from' => 1,
        'ayah_to' => 5,
        'rating' => 'excellent',
        'performed_at' => now(),
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->get(route('admin.circles.show', $circle->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Circles/Show')
            ->where('circle.name', 'حلقة الفجر')
            ->where('circle.teacher.id', $teacher->id)
            ->where('summary.total_students', 1)
            ->where('summary.recitations_this_month', 1)
            ->where('students.0.student.id', $student->id)
            ->where('students.0.memorized_ayahs', 5)
            ->where('students.0.recitation_count', 1)
            ->where('topStudent.student.id', $student->id)
        );
});

test('org-admin cannot view a circle from another organization (IDOR protection)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedRolesForCircleDetails($orgA->id);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    $adminA->assignRole($rolesA['org-admin']);

    $orgB = Organization::factory()->create();
    $circleB = Circle::factory()->create(['organization_id' => $orgB->id]);

    $this->actingAs($adminA)
        ->get(route('admin.circles.show', $circleB->id))
        ->assertNotFound();
});

test('a supervisor can view the executive dashboard of a circle in their organization', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForCircleDetails($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor)
        ->get(route('admin.circles.show', $circle->id))
        ->assertOk();
});

test('super-admin can view the executive dashboard of a circle', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForCircleDetails($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    // super-admin يملك organization_id = المنظمة (وليس null) ليطابق team context دوره.
    $superAdmin = User::factory()->create([
        'organization_id' => $org->id,
        'email' => 'super-admin@example.com',
    ]);
    $superAdmin->assignRole($roles['super-admin']);

    $this->post('/login', [
        'email' => $superAdmin->email,
        'password' => 'password',
    ]);

    $this->get(route('admin.circles.show', $circle->id))->assertOk();
});
