<?php

declare(strict_types=1);

use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function bulkRoles(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * @param  array<string, mixed>  $roles
 */
function bulkActor(Organization $org, array $roles, string $role = 'org-admin'): User
{
    $user = User::factory()->create(['organization_id' => $org->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user->assignRole($roles[$role]);

    return $user;
}

function bulkCircle(Organization $org, ?User $teacher = null): Circle
{
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    return Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher?->id,
    ]);
}

function bulkStudent(Organization $org, Circle $circle): StudentProfile
{
    return StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'status' => StudentStatus::Active,
    ]);
}

/**
 * @param  list<array<string, mixed>>  $records
 * @return array<string, mixed>
 */
function bulkPayload(Circle $circle, array $records): array
{
    return [
        'circle_id' => $circle->id,
        'date' => now()->toDateString(),
        'records' => $records,
    ];
}

test('a teacher saves the whole daily sheet in one request', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $circle = bulkCircle($org, $teacher);
    $first = bulkStudent($org, $circle);
    $second = bulkStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $first->id, 'status' => 'present', 'notes' => null],
            ['student_id' => $second->id, 'status' => 'absent'],
        ]))
        ->assertRedirect();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(2);

    $this->assertDatabaseHas('student_attendances', [
        'organization_id' => $org->id,
        'student_id' => $first->id,
        'circle_id' => $circle->id,
        'status' => 'present',
        'recorded_by' => $teacher->id,
    ]);

    $this->assertDatabaseHas('student_attendances', [
        'organization_id' => $org->id,
        'student_id' => $second->id,
        'status' => 'absent',
    ]);

    // upsert لا يُطلق أحداث Eloquent: تدقيق واحد مُجمَّع بدل سجل لكل طالب.
    expect(AuditLog::withoutGlobalScopes()->where('event', 'attendance.bulk_recorded')->count())->toBe(1)
        ->and(AuditLog::withoutGlobalScopes()->where('event', 'student_attendance.created')->count())->toBe(0);
});

test('saving the sheet again corrects records instead of duplicating them', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $circle = bulkCircle($org, $teacher);
    $student = bulkStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $student->id, 'status' => 'present'],
        ]))
        ->assertRedirect();

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $student->id, 'status' => 'excused', 'notes' => 'إذن مسبق'],
        ]))
        ->assertRedirect();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(1);

    $attendance = StudentAttendance::withoutGlobalScopes()->firstOrFail();

    expect($attendance->status->value)->toBe('excused')
        ->and($attendance->notes)->toBe('إذن مسبق');
});

test('a student from another circle is rejected', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $circle = bulkCircle($org, $teacher);
    $otherCircle = bulkCircle($org, $teacher);
    $foreignStudent = bulkStudent($org, $otherCircle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $foreignStudent->id, 'status' => 'present'],
        ]))
        ->assertSessionHasErrors('records.0.student_id');

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('duplicate students in one sheet are rejected', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $circle = bulkCircle($org, $teacher);
    $student = bulkStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $student->id, 'status' => 'present'],
            ['student_id' => $student->id, 'status' => 'absent'],
        ]))
        ->assertSessionHasErrors('records.1.student_id');

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('a teacher cannot save the sheet of another teacher circle', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $otherTeacher = bulkActor($org, $roles, 'teacher');

    bulkCircle($org, $teacher);
    $foreignCircle = bulkCircle($org, $otherTeacher);
    $foreignStudent = bulkStudent($org, $foreignCircle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), bulkPayload($foreignCircle, [
            ['student_id' => $foreignStudent->id, 'status' => 'present'],
        ]))
        ->assertSessionHasErrors('records.0.student_id');

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('a future date is rejected for the whole sheet', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $teacher = bulkActor($org, $roles, 'teacher');
    $circle = bulkCircle($org, $teacher);
    $student = bulkStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.bulk'), [
            'circle_id' => $circle->id,
            'date' => now()->addDay()->toDateString(),
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ])
        ->assertSessionHasErrors('date');

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('a student cannot save a sheet', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $circle = bulkCircle($org);
    $student = bulkStudent($org, $circle);

    $this->actingAs($student->user)
        ->post(route('attendance.bulk'), bulkPayload($circle, [
            ['student_id' => $student->id, 'status' => 'present'],
        ]))
        ->assertForbidden();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('the admin history only lists records of the organization', function () {
    $org = Organization::factory()->create();
    $roles = bulkRoles($org->id);

    $otherOrg = Organization::factory()->create();
    $otherRoles = bulkRoles($otherOrg->id);

    $admin = bulkActor($org, $roles);
    $circle = bulkCircle($org);
    $student = bulkStudent($org, $circle);

    $foreignAdmin = bulkActor($otherOrg, $otherRoles);
    $foreignCircle = bulkCircle($otherOrg, $foreignAdmin);
    $foreignStudent = bulkStudent($otherOrg, $foreignCircle);

    $this->actingAs($admin)->post(route('attendance.bulk'), bulkPayload($circle, [
        ['student_id' => $student->id, 'status' => 'present'],
    ]))->assertRedirect();

    $this->actingAs($foreignAdmin)->post(route('attendance.bulk'), bulkPayload($foreignCircle, [
        ['student_id' => $foreignStudent->id, 'status' => 'present'],
    ]))->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Attendances/Index')
            ->has('attendances.data', 1)
            ->where('attendances.data.0.student_id', $student->id));
});
