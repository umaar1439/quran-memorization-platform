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
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function attendanceRoles(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * مستخدم بدور محدد داخل فريق (Team) منظمة معينة — setPermissionsTeamId قبل
 * الإسناد ضروري عند تعدد المنظمات في نفس الاختبار.
 *
 * @param  array<string, mixed>  $roles
 */
function attendanceActor(Organization $org, array $roles, string $role = 'org-admin'): User
{
    $user = User::factory()->create(['organization_id' => $org->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user->assignRole($roles[$role]);

    return $user;
}

/** مسجد + حلقة داخل المنظمة، مع إسناد المعلم اختيارياً. */
function attendanceCircle(Organization $org, ?User $teacher = null): Circle
{
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    return Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher?->id,
    ]);
}

function attendanceStudent(Organization $org, Circle $circle): StudentProfile
{
    return StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'status' => StudentStatus::Active,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function attendancePayload(StudentProfile $student, array $overrides = []): array
{
    return [
        'student_id' => $student->id,
        'status' => 'present',
        'date' => now()->toDateString(),
        'notes' => 'حاضر',
        ...$overrides,
    ];
}

test('a teacher records attendance for a student in their own circle', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $teacher = attendanceActor($org, $roles, 'teacher');
    $circle = attendanceCircle($org, $teacher);
    $student = attendanceStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.store'), attendancePayload($student))
        ->assertRedirect();

    $this->assertDatabaseHas('student_attendances', [
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'circle_id' => $circle->id,
        'mosque_id' => $student->mosque_id,
        'status' => 'present',
        'recorded_by' => $teacher->id,
        'date' => now()->toDateString(),
    ]);

    // التسجيل الفردي يمر بأحداث Eloquent فيُدقَّق تلقائياً عبر AuditModelChanges.
    expect(AuditLog::withoutGlobalScopes()->where('event', 'student_attendance.created')->count())->toBe(1);
});

test('recording the same student twice on the same day corrects the existing row', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $teacher = attendanceActor($org, $roles, 'teacher');
    $circle = attendanceCircle($org, $teacher);
    $student = attendanceStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.store'), attendancePayload($student))
        ->assertRedirect();

    $this->actingAs($teacher)
        ->post(route('attendance.store'), attendancePayload($student, [
            'status' => 'late',
            'notes' => 'تأخر 10 دقائق',
        ]))
        ->assertRedirect();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(1);

    $attendance = StudentAttendance::withoutGlobalScopes()->firstOrFail();

    expect($attendance->status->value)->toBe('late')
        ->and($attendance->notes)->toBe('تأخر 10 دقائق');
});

test('a teacher cannot record attendance for a student outside their circles', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $teacher = attendanceActor($org, $roles, 'teacher');
    $otherTeacher = attendanceActor($org, $roles, 'teacher');

    attendanceCircle($org, $teacher);
    $foreignCircle = attendanceCircle($org, $otherTeacher);
    $foreignStudent = attendanceStudent($org, $foreignCircle);

    $this->actingAs($teacher)
        ->post(route('attendance.store'), attendancePayload($foreignStudent))
        ->assertForbidden();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('an admin from another organization cannot record attendance', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $otherOrg = Organization::factory()->create();
    $otherRoles = attendanceRoles($otherOrg->id);

    $circle = attendanceCircle($org);
    $student = attendanceStudent($org, $circle);

    $foreignAdmin = attendanceActor($otherOrg, $otherRoles);

    $this->actingAs($foreignAdmin)
        ->post(route('attendance.store'), attendancePayload($student))
        ->assertForbidden();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('a future date is rejected', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $teacher = attendanceActor($org, $roles, 'teacher');
    $circle = attendanceCircle($org, $teacher);
    $student = attendanceStudent($org, $circle);

    $this->actingAs($teacher)
        ->post(route('attendance.store'), attendancePayload($student, [
            'date' => now()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors('date');

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('a student cannot record attendance', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $circle = attendanceCircle($org);
    $student = attendanceStudent($org, $circle);

    $this->actingAs($student->user)
        ->post(route('attendance.store'), attendancePayload($student))
        ->assertForbidden();

    expect(StudentAttendance::withoutGlobalScopes()->count())->toBe(0);
});

test('the teacher attendance page lists only the teacher circles', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $teacher = attendanceActor($org, $roles, 'teacher');
    $circle = attendanceCircle($org, $teacher);
    attendanceStudent($org, $circle);
    attendanceStudent($org, $circle);

    $otherTeacher = attendanceActor($org, $roles, 'teacher');
    $otherCircle = attendanceCircle($org, $otherTeacher);
    attendanceStudent($org, $otherCircle);

    $this->actingAs($teacher)
        ->get(route('teacher.attendance.page'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/AttendanceSheet')
            ->where('sheet.circle_id', $circle->id)
            ->has('sheet.circles', 1)
            ->has('sheet.rows', 2)
            ->where('sheet.rows.0.status', null));
});

test('the admin attendance page shows the sheet and the history log', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $admin = attendanceActor($org, $roles);
    $circle = attendanceCircle($org);
    $student = attendanceStudent($org, $circle);

    $this->actingAs($admin)
        ->post(route('attendance.store'), attendancePayload($student))
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Attendances/Index')
            ->has('sheet.rows', 1)
            ->where('sheet.rows.0.status', 'present')
            ->has('attendances.data', 1)
            ->where('attendances.data.0.status', 'present'));
});

test('the daily sheet keeps a constant query count as students are added', function () {
    $org = Organization::factory()->create();
    $roles = attendanceRoles($org->id);

    $admin = attendanceActor($org, $roles);
    $circle = attendanceCircle($org);
    attendanceStudent($org, $circle);

    $this->actingAs($admin);

    $measure = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('admin.attendance.index'))->assertOk();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    };

    $baseline = $measure();

    StudentProfile::factory()->count(8)->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'status' => StudentStatus::Active,
    ]);

    // سماحية صغيرة لفرق مشروع (عدّاد الترقيم)، لا نمو خطي بعدد الطلاب.
    expect($measure())->toBeLessThanOrEqual($baseline + 2);
});
