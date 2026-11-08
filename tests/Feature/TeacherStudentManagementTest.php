<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedTeacherStudentRoles(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('creating a teacher without any circle is rejected with 422', function () {
    $org = Organization::factory()->create();
    $roles = seedTeacherStudentRoles($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->postJson(route('admin.teachers.store'), [
            'name' => 'معلم بلا حلقة',
            'email' => 'no-circle-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('circles');

    $this->assertDatabaseMissing('users', ['email' => 'no-circle-teacher@example.com']);
});

test('a teacher can be created and linked to one or more circles', function () {
    $org = Organization::factory()->create();
    $roles = seedTeacherStudentRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circleA = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $circleB = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم جديد',
            'email' => 'new-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'specialization' => 'تجويد',
            'circles' => [$circleA->id, $circleB->id],
        ])
        ->assertRedirect();

    $teacher = User::where('email', 'new-teacher@example.com')->first();

    expect($teacher)->not->toBeNull();
    expect($teacher->hasRole('teacher'))->toBeTrue();
    expect($teacher->teacherProfile)->not->toBeNull();
    expect($teacher->teacherProfile->organization_id)->toBe($org->id);

    expect($circleA->fresh()->teacher_id)->toBe($teacher->id);
    expect($circleB->fresh()->teacher_id)->toBe($teacher->id);
});

test('a student can be created and linked to a valid circle', function () {
    $org = Organization::factory()->create();
    $roles = seedTeacherStudentRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->post(route('admin.students.store'), [
            'name' => 'طالب جديد',
            'email' => 'new-student@example.com',
            'password' => 'password',
            'enrollment_number' => 'STU-90001',
            'circle_id' => $circle->id,
            'status' => 'active',
        ])
        ->assertRedirect();

    $studentUser = User::where('email', 'new-student@example.com')->first();

    expect($studentUser)->not->toBeNull();
    expect($studentUser->hasRole('student'))->toBeTrue();

    $profile = $studentUser->studentProfile;
    expect($profile)->not->toBeNull();
    expect($profile->circle_id)->toBe($circle->id);
    expect($profile->mosque_id)->toBe($circle->mosque_id);
    expect($profile->organization_id)->toBe($org->id);
});

test('a teacher cannot be assigned circles from another organization (tenant isolation)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedTeacherStudentRoles($orgA->id);

    $orgB = Organization::factory()->create();
    seedTeacherStudentRoles($orgB->id);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);
    $circleB = Circle::factory()->create(['organization_id' => $orgB->id, 'mosque_id' => $mosqueB->id]);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    $adminA->assignRole($rolesA['org-admin']);

    $this->actingAs($adminA)
        ->postJson(route('admin.teachers.store'), [
            'name' => 'معلم',
            'email' => 'cross-org-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [$circleB->id],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('circles.0');

    $this->assertDatabaseMissing('users', ['email' => 'cross-org-teacher@example.com']);
});

test('a student cannot be assigned a circle from another organization (tenant isolation)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedTeacherStudentRoles($orgA->id);

    $orgB = Organization::factory()->create();
    seedTeacherStudentRoles($orgB->id);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);
    $circleB = Circle::factory()->create(['organization_id' => $orgB->id, 'mosque_id' => $mosqueB->id]);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    $adminA->assignRole($rolesA['org-admin']);

    $this->actingAs($adminA)
        ->postJson(route('admin.students.store'), [
            'name' => 'طالب',
            'email' => 'cross-org-student@example.com',
            'password' => 'password',
            'enrollment_number' => 'STU-90002',
            'circle_id' => $circleB->id,
            'status' => 'active',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('circle_id');

    $this->assertDatabaseMissing('users', ['email' => 'cross-org-student@example.com']);
});

test('an org-admin cannot update a teacher from another organization (tenant isolation)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedTeacherStudentRoles($orgA->id);

    $orgB = Organization::factory()->create();
    $rolesB = seedTeacherStudentRoles($orgB->id);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);
    $circleB = Circle::factory()->create(['organization_id' => $orgB->id, 'mosque_id' => $mosqueB->id]);

    $teacherB = User::factory()->create(['organization_id' => $orgB->id]);
    $teacherB->assignRole($rolesB['teacher']);

    $teacherProfileB = TeacherProfile::query()->create([
        'user_id' => $teacherB->id,
        'organization_id' => $orgB->id,
    ]);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    $adminA->assignRole($rolesA['org-admin']);

    $this->actingAs($adminA)
        ->patchJson(route('admin.teachers.update', $teacherProfileB->id), [
            'name' => 'مخترق',
            'email' => $teacherB->email,
            'status' => 'active',
            'circles' => [$circleB->id],
        ])
        ->assertNotFound();
});
