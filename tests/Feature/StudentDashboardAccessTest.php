<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedStudentAccessRoles(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * ينشئ منظمة + مسجد + حلقتين (حلقة للمعلم وأخرى مختلفة) + طالبين.
 */
function makeStudentAccessFixture(): array
{
    $org = Organization::factory()->create();
    $roles = seedStudentAccessRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $teacherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $otherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $teacherCircle->update(['teacher_id' => $teacher->id]);

    $ownStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $teacherCircle->id,
    ]);
    $outsideStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $otherCircle->id,
    ]);

    return compact('org', 'roles', 'mosque', 'teacherCircle', 'otherCircle', 'teacher', 'ownStudent', 'outsideStudent');
}

test('org-admin can view the dashboard of any student in their organization', function () {
    ['org' => $org, 'roles' => $roles, 'ownStudent' => $ownStudent, 'outsideStudent' => $outsideStudent] = makeStudentAccessFixture();

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->get(route('students.show', $ownStudent->id))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('students.show', $outsideStudent->id))
        ->assertOk();
});

test('supervisor can view the dashboard of any student in their organization', function () {
    ['org' => $org, 'roles' => $roles, 'ownStudent' => $ownStudent, 'outsideStudent' => $outsideStudent] = makeStudentAccessFixture();

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor)
        ->get(route('students.show', $ownStudent->id))
        ->assertOk();

    $this->actingAs($supervisor)
        ->get(route('students.show', $outsideStudent->id))
        ->assertOk();
});

test('super-admin can view the dashboard of any student', function () {
    ['org' => $org, 'roles' => $roles, 'ownStudent' => $ownStudent] = makeStudentAccessFixture();

    $superAdmin = User::factory()->create([
        'organization_id' => $org->id,
        'email' => 'super-admin@example.com',
    ]);
    $superAdmin->assignRole($roles['super-admin']);

    $this->actingAs($superAdmin)
        ->get(route('students.show', $ownStudent->id))
        ->assertOk();
});

test('teacher can view the dashboard of a student enrolled in their circle', function () {
    ['teacher' => $teacher, 'ownStudent' => $ownStudent] = makeStudentAccessFixture();

    $this->actingAs($teacher)
        ->get(route('students.show', $ownStudent->id))
        ->assertOk();
});

test('teacher is forbidden from viewing the dashboard of a student outside their circle', function () {
    ['teacher' => $teacher, 'outsideStudent' => $outsideStudent] = makeStudentAccessFixture();

    $this->actingAs($teacher)
        ->get(route('students.show', $outsideStudent->id))
        ->assertForbidden();
});

test('org-admin cannot view a student from another organization (tenant isolation)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedStudentAccessRoles($orgA->id);

    $orgB = Organization::factory()->create();
    seedStudentAccessRoles($orgB->id);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);
    $circleB = Circle::factory()->create(['organization_id' => $orgB->id, 'mosque_id' => $mosqueB->id]);
    $studentB = StudentProfile::factory()->create([
        'organization_id' => $orgB->id,
        'mosque_id' => $mosqueB->id,
        'circle_id' => $circleB->id,
    ]);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    $adminA->assignRole($rolesA['org-admin']);

    $this->actingAs($adminA)
        ->get(route('students.show', $studentB->id))
        ->assertNotFound();
});
