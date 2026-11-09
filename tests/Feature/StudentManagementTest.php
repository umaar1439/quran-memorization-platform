<?php

declare(strict_types=1);

use App\Actions\CreateStudentAction;
use App\DTOs\StudentData;
use App\Enums\GenericStatus;
use App\Enums\StudentStatus;
use App\Models\AuditLog;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForStudentManagement(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * منظمة + أدوارها + مدير منظمة (الفاعل الافتراضي).
 *
 * @return array{0: Organization, 1: array<string, Role>, 2: User}
 */
function studentManagementContext(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForStudentManagement($org->id);

    return [$org, $roles, studentAdminFor($org, $roles)];
}

/**
 * مستخدم بدور محدد داخل فريق (Team) منظمة معينة — ضروري عند تعدد المنظمات
 * لأن setPermissionsTeamId يُضبط صراحةً قبل الإسناد.
 */
function studentAdminFor(Organization $org, array $roles, string $role = 'org-admin'): User
{
    $user = User::factory()->create(['organization_id' => $org->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $user->assignRole($roles[$role]);

    return $user;
}

/**
 * مسجد + حلقة داخل المنظمة (نشطة افتراضياً).
 *
 * @param  array<string, mixed>  $overrides
 */
function studentCircle(Organization $org, array $overrides = []): Circle
{
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    return Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        ...$overrides,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentPayload(int $circleId, array $overrides = []): array
{
    return [
        'name' => 'طالب جديد',
        'email' => 'student@example.com',
        'password' => 'password',
        'enrollment_number' => 'STU-1001',
        'birth_date' => '2010-05-01',
        'current_juz' => 3,
        'circle_id' => $circleId,
        'status' => 'active',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function studentUpdatePayload(int $circleId, array $overrides = []): array
{
    return [
        'name' => 'طالب محدَّث',
        'email' => 'student@example.com',
        'password' => '',
        'enrollment_number' => 'STU-1001',
        'birth_date' => '',
        'current_juz' => '',
        'circle_id' => $circleId,
        'status' => 'active',
        ...$overrides,
    ];
}

test('an org-admin creates a student with a linked account and the creation is audited', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);

    $this->actingAs($admin)
        ->post(route('admin.students.store'), studentPayload($circle->id))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $profile = StudentProfile::withoutGlobalScopes()
        ->where('enrollment_number', 'STU-1001')
        ->firstOrFail();

    expect($profile->organization_id)->toBe($org->id)
        ->and($profile->mosque_id)->toBe($circle->mosque_id)
        ->and($profile->circle_id)->toBe($circle->id)
        ->and($profile->current_juz)->toBe(3)
        ->and($profile->status)->toBe(StudentStatus::Active);

    $user = User::query()->findOrFail($profile->user_id);

    expect($user->hasRole('student'))->toBeTrue()
        ->and($user->organization_id)->toBe($org->id)
        ->and(Hash::check('password', $user->password))->toBeTrue();

    $audit = AuditLog::withoutGlobalScopes()
        ->where('event', 'student.created')
        ->where('auditable_id', (string) $profile->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->organization_id)->toBe($org->id);
});

test('a student cannot be assigned a circle from another organization', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedRolesForStudentManagement($orgA->id);

    $orgB = Organization::factory()->create();
    seedRolesForStudentManagement($orgB->id);

    $circleB = studentCircle($orgB);
    $adminA = studentAdminFor($orgA, $rolesA);

    $this->actingAs($adminA)
        ->postJson(route('admin.students.store'), studentPayload($circleB->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors('circle_id');

    $this->assertDatabaseCount('student_profiles', 0);
    $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
});

test('a student cannot be assigned a soft-deleted circle', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);
    $circle->delete();

    $this->actingAs($admin)
        ->postJson(route('admin.students.store'), studentPayload($circle->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors('circle_id');
});

test('a student cannot be assigned an inactive circle', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org, ['status' => GenericStatus::Inactive]);

    $this->actingAs($admin)
        ->postJson(route('admin.students.store'), studentPayload($circle->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors('circle_id');
});

test('the enrollment number is unique inside an organization only', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedRolesForStudentManagement($orgA->id);

    $orgB = Organization::factory()->create();
    $rolesB = seedRolesForStudentManagement($orgB->id);

    $circleA = studentCircle($orgA);
    $circleB = studentCircle($orgB);

    $adminA = studentAdminFor($orgA, $rolesA);
    $adminB = studentAdminFor($orgB, $rolesB);

    $this->actingAs($adminA)
        ->post(route('admin.students.store'), studentPayload($circleA->id))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // نفس رقم القيد في منظمة أخرى مسموح (فهرس مركّب org+enrollment).
    $this->actingAs($adminB)
        ->post(route('admin.students.store'), studentPayload($circleB->id, [
            'email' => 'second-org-student@example.com',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(StudentProfile::withoutGlobalScopes()->where('enrollment_number', 'STU-1001')->count())->toBe(2);

    // التكرار داخل المنظمة نفسها مرفوض بـ 422 على الحقل.
    $this->actingAs($adminA)
        ->postJson(route('admin.students.store'), studentPayload($circleA->id, [
            'email' => 'duplicate-in-org@example.com',
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('enrollment_number');
});

test('a teacher without students.manage cannot create a student', function () {
    [$org, $roles, $admin] = studentManagementContext();
    $circle = studentCircle($org);
    $teacher = studentAdminFor($org, $roles, 'teacher');

    expect($teacher->can('create', StudentProfile::class))->toBeFalse();

    $this->actingAs($teacher)
        ->postJson(route('admin.students.store'), studentPayload($circle->id))
        ->assertForbidden();

    $this->assertDatabaseCount('student_profiles', 0);
});

test('a supervisor holding students.manage can create a student', function () {
    [$org, $roles] = studentManagementContext();
    $circle = studentCircle($org);
    $supervisor = studentAdminFor($org, $roles, 'supervisor');

    $this->actingAs($supervisor)
        ->post(route('admin.students.store'), studentPayload($circle->id))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(StudentProfile::withoutGlobalScopes()->count())->toBe(1);
});

test('an actor without an organization context cannot create a student (fail closed)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForStudentManagement($org->id);
    $circle = studentCircle($org);

    $actor = User::factory()->create(['organization_id' => null]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    $actor->assignRole($roles['org-admin']);

    expect($actor->can('create', StudentProfile::class))->toBeFalse();

    // الـ Action نفسه يرفض أيضاً لو استُدعي من مسار غير HTTP.
    $action = app(CreateStudentAction::class);
    $data = StudentData::fromArray(studentPayload($circle->id));

    expect(fn () => $action->execute($data, $actor))->toThrow(HttpException::class);

    $this->actingAs($actor)
        ->postJson(route('admin.students.store'), studentPayload($circle->id))
        ->assertForbidden();

    $this->assertDatabaseCount('student_profiles', 0);
});

test('an org-admin updates a student, moves the circle and the change is audited', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);
    $newCircle = studentCircle($org);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $user = $student->user;

    $this->actingAs($admin)
        ->patch(route('admin.students.update', $student->id), studentUpdatePayload($newCircle->id, [
            'email' => $user->email,
            'password' => 'new-password',
            'status' => 'transferred',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $student->refresh();
    $user->refresh();

    expect($student->circle_id)->toBe($newCircle->id)
        ->and($student->mosque_id)->toBe($newCircle->mosque_id)
        ->and($student->status)->toBe(StudentStatus::Transferred)
        ->and($student->current_juz)->toBeNull()
        ->and($user->name)->toBe('طالب محدَّث')
        ->and(Hash::check('new-password', $user->password))->toBeTrue();

    $audit = AuditLog::withoutGlobalScopes()
        ->where('event', 'student.updated')
        ->where('auditable_id', (string) $student->id)
        ->first();

    expect($audit)->not->toBeNull();
});

test('leaving the password blank on update keeps the stored password', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $user = $student->user;
    $oldHash = $user->password;

    $this->actingAs($admin)
        ->patch(route('admin.students.update', $student->id), studentUpdatePayload($circle->id, [
            'email' => $user->email,
            'password' => '',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($user->fresh()->password)->toBe($oldHash);
});

test('the current circle of a student stays assignable after it is deactivated', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $circle->update(['status' => GenericStatus::Inactive]);

    // لا يُحظر تعديل بيانات الطالب لمجرد تعطيل حلقته الحالية.
    $this->actingAs($admin)
        ->patch(route('admin.students.update', $student->id), studentUpdatePayload($circle->id, [
            'email' => $student->user->email,
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($student->fresh()->circle_id)->toBe($circle->id);
});

test('moving a student into another inactive circle is rejected', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);
    $inactiveCircle = studentCircle($org, ['status' => GenericStatus::Inactive]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.students.update', $student->id), studentUpdatePayload($inactiveCircle->id, [
            'email' => $student->user->email,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('circle_id');
});

test('a student of another organization cannot be updated or deleted (IDOR)', function () {
    $orgA = Organization::factory()->create();
    $rolesA = seedRolesForStudentManagement($orgA->id);

    $orgB = Organization::factory()->create();
    seedRolesForStudentManagement($orgB->id);

    $circleB = studentCircle($orgB);
    $student = StudentProfile::factory()->create([
        'organization_id' => $orgB->id,
        'mosque_id' => $circleB->mosque_id,
        'circle_id' => $circleB->id,
        'enrollment_number' => 'STU-3001',
    ]);

    $adminA = studentAdminFor($orgA, $rolesA);

    $this->actingAs($adminA)
        ->patchJson(route('admin.students.update', $student->id), studentUpdatePayload($circleB->id, [
            'email' => $student->user->email,
        ]))
        ->assertNotFound();

    $this->actingAs($adminA)
        ->delete(route('admin.students.destroy', $student->id))
        ->assertNotFound();

    expect($student->fresh()->trashed())->toBeFalse();
});

test('deleting a student soft-deletes the profile and its account and is audited', function () {
    [$org, , $admin] = studentManagementContext();
    $circle = studentCircle($org);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $circle->mosque_id,
        'circle_id' => $circle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $userId = $student->user_id;

    $this->actingAs($admin)
        ->delete(route('admin.students.destroy', $student->id))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(StudentProfile::withTrashed()->find($student->id)?->trashed())->toBeTrue()
        ->and(User::withTrashed()->find($userId)?->trashed())->toBeTrue();

    foreach (['student.deleted', 'user.deleted'] as $event) {
        $audit = AuditLog::withoutGlobalScopes()->where('event', $event)->first();

        expect($audit)->not->toBeNull();
    }
});

test('the students list filters by circle, mosque, status and search inside the tenant', function () {
    [$org, , $admin] = studentManagementContext();

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circleA = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $circleB = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $active = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circleA->id,
    ]);
    $graduated = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circleB->id,
        'status' => StudentStatus::Graduated,
    ]);

    // طالب منظمة أخرى يجب ألا يظهر إطلاقاً.
    $otherOrg = Organization::factory()->create();
    $otherCircle = studentCircle($otherOrg);
    StudentProfile::factory()->create([
        'organization_id' => $otherOrg->id,
        'mosque_id' => $otherCircle->mosque_id,
        'circle_id' => $otherCircle->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Students/Index')
            ->has('students.data', 2)
            ->has('circles', 2)
            ->has('mosques', 1));

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['circle_id' => $circleA->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $active->id));

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['status' => 'graduated']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $graduated->id));

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['mosque_id' => $mosque->id]))
        ->assertInertia(fn (Assert $page) => $page->has('students.data', 2));

    $this->actingAs($admin)
        ->get(route('admin.students.index', ['search' => $active->user->name]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 1)
            ->where('students.data.0.id', $active->id));
});

test('a transfer is audited exactly once as student.transferred', function () {
    [$org, , $admin] = studentManagementContext();
    $fromCircle = studentCircle($org);
    $toCircle = studentCircle($org);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $fromCircle->mosque_id,
        'circle_id' => $fromCircle->id,
        'enrollment_number' => 'STU-1001',
    ]);

    $this->actingAs($admin)
        ->postJson('/student-transfers', [
            'student_id' => $student->id,
            'to_mosque_id' => $toCircle->mosque_id,
            'to_circle_id' => $toCircle->id,
            'reason' => 'نقل داخل نفس المنظمة',
        ])
        ->assertCreated();

    // saveQuietly في TransferStudentAction يمنع ازدواج السجل عبر مستمع
    // AuditModelChanges (student.updated) — سطر واحد فقط للحدث النطاقي.
    $events = AuditLog::withoutGlobalScopes()
        ->where('auditable_id', (string) $student->id)
        ->whereIn('event', ['student.transferred', 'student.updated'])
        ->pluck('event')
        ->all();

    expect($events)->toBe(['student.transferred'])
        ->and($student->fresh()->circle_id)->toBe($toCircle->id);
});
