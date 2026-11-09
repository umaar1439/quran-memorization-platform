<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Queries\UserDirectoryQuery;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * ينشئ الصلاحيات والأدوار لمنظمة معينة ويعيد خريطة role => Role.
 */
function seedRolesForUserManagement(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * منظمة + أدوارها + مدير منظمة (الفاعل الافتراضي في أغلب الاختبارات).
 *
 * @return array{0: Organization, 1: array<string, Role>, 2: User}
 */
function userManagementContext(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForUserManagement($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    return [$org, $roles, $admin];
}

/**
 * مستخدم داخل المنظمة بدور محدد (الهدف الذي يُدار).
 *
 * @param  array<string, mixed>  $attributes
 */
function managedUser(Organization $organization, Role $role, array $attributes = []): User
{
    $user = User::factory()->create(['organization_id' => $organization->id, ...$attributes]);
    $user->assignRole($role);

    return $user;
}

/**
 * حمولة تعديل صالحة لهدف محدد (نفس مفاتيح UpdateUserRequest).
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function managedUserPayload(User $target, array $overrides = []): array
{
    return [
        'name' => $target->name,
        'email' => $target->email,
        'password' => '',
        'phone' => (string) ($target->phone ?? ''),
        'role' => (string) ($target->getRoleNames()->first() ?? 'teacher'),
        'status' => 'active',
        ...$overrides,
    ];
}

test('an org-admin creates a user in their own organization with the chosen role', function () {
    [$org, , $admin] = userManagementContext();

    $this->actingAs($admin);

    // Regression: كان هذا الطلب يفشل بـ 500 لأن organization_id/status
    // تُمرَّر إلى User::create() وهما خارج $fillable (Mass Assignment).
    $this->post(route('admin.users.store'), [
        'name' => 'مشرف جديد',
        'email' => 'new-supervisor@example.com',
        'password' => 'Password123!',
        'phone' => '0551234567',
        'role' => 'supervisor',
        'status' => 'active',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $created = User::query()->where('email', 'new-supervisor@example.com')->firstOrFail();

    expect($created->organization_id)->toBe($org->id)
        ->and($created->name)->toBe('مشرف جديد')
        ->and($created->phone)->toBe('0551234567')
        ->and($created->status)->toBe(UserStatus::Active)
        ->and(Hash::check('Password123!', $created->password))->toBeTrue()
        ->and($created->hasRole('supervisor'))->toBeTrue();

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'user.created')
        ->where('auditable_id', (string) $created->id)
        ->exists())->toBeTrue();

    // الدور ليس عموداً على users؛ يُسجَّل صراحةً.
    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'user.roles_synced')
        ->where('auditable_id', (string) $created->id)
        ->exists())->toBeTrue();
});

test('creating a user validates the email uniqueness and the assignable roles whitelist', function () {
    [$org, $roles, $admin] = userManagementContext();
    managedUser($org, $roles['teacher'], ['email' => 'taken@example.com']);

    $this->actingAs($admin);

    $this->from('/admin/users')->post(route('admin.users.store'), [
        'name' => 'مستخدم',
        'email' => 'taken@example.com',
        'password' => 'Password123!',
        'role' => 'super-admin', // غير قابل للإسناد من الواجهة
        'status' => 'active',
    ])->assertSessionHasErrors(['email', 'role']);

    expect(User::query()->count())->toBe(2);
});

test('a supervisor cannot create a higher-ranked user even with the create permission', function () {
    [$org, $roles] = userManagementContext();

    $supervisor = managedUser($org, $roles['supervisor']);
    $supervisor->givePermissionTo(['users.view', 'users.create']);

    $this->actingAs($supervisor);

    $this->from('/admin/users')->post(route('admin.users.store'), [
        'name' => 'مدير منظمة',
        'email' => 'peer-admin@example.com',
        'password' => 'Password123!',
        'role' => 'org-admin',
        'status' => 'active',
    ])->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'peer-admin@example.com')->exists())->toBeFalse();
});

test('a supervisor can create a lower-ranked user', function () {
    [$org, $roles] = userManagementContext();

    $supervisor = managedUser($org, $roles['supervisor']);
    $supervisor->givePermissionTo(['users.view', 'users.create']);

    $this->actingAs($supervisor);

    $this->post(route('admin.users.store'), [
        'name' => 'معلم جديد',
        'email' => 'new-teacher@example.com',
        'password' => 'Password123!',
        'role' => 'teacher',
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $created = User::query()->where('email', 'new-teacher@example.com')->firstOrFail();

    expect($created->organization_id)->toBe($org->id)
        ->and($created->hasRole('teacher'))->toBeTrue();
});

test('updating a user leaves the password untouched when the field is blank', function () {
    [$org, $roles, $admin] = userManagementContext();
    $teacher = managedUser($org, $roles['teacher']);
    $originalHash = $teacher->password;

    $this->actingAs($admin);

    $this->patch(
        route('admin.users.update', $teacher),
        managedUserPayload($teacher, [
            'name' => 'اسم محدّث',
            'phone' => '0500000000',
            'status' => 'suspended',
        ]),
    )->assertRedirect()->assertSessionHasNoErrors();

    $teacher->refresh();

    expect($teacher->name)->toBe('اسم محدّث')
        ->and($teacher->phone)->toBe('0500000000')
        ->and($teacher->status)->toBe(UserStatus::Suspended)
        ->and($teacher->password)->toBe($originalHash);

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'user.password_changed')
        ->where('auditable_id', (string) $teacher->id)
        ->exists())->toBeFalse();

    // لا تغيير في الدور = لا سجل roles_synced (تفادي ضجيج التدقيق).
    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'user.roles_synced')
        ->where('auditable_id', (string) $teacher->id)
        ->exists())->toBeFalse();
});

test('changing a user password is audited without storing the secret', function () {
    [$org, $roles, $admin] = userManagementContext();
    $student = managedUser($org, $roles['student']);

    $this->actingAs($admin);

    $this->patch(
        route('admin.users.update', $student),
        managedUserPayload($student, ['password' => 'NewPassword123!']),
    )->assertSessionHasNoErrors();

    expect(Hash::check('NewPassword123!', $student->fresh()->password))->toBeTrue();

    $log = AuditLog::withoutGlobalScopes()
        ->where('event', 'user.password_changed')
        ->where('auditable_id', (string) $student->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->new_values)->toBe(['password' => '[REDACTED]']);
});

test('changing a user role is audited with the previous and the new role', function () {
    [$org, $roles, $admin] = userManagementContext();
    $teacher = managedUser($org, $roles['teacher']);

    $this->actingAs($admin);

    $this->patch(
        route('admin.users.update', $teacher),
        managedUserPayload($teacher, ['role' => 'supervisor']),
    )->assertSessionHasNoErrors();

    expect($teacher->fresh()->hasRole('supervisor'))->toBeTrue();

    $log = AuditLog::withoutGlobalScopes()
        ->where('event', 'user.roles_synced')
        ->where('auditable_id', (string) $teacher->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values)->toBe(['roles' => ['teacher']]);
    expect($log->new_values)->toBe(['roles' => ['supervisor']]);
});

test('an org-admin cannot update or delete their own account through user management', function () {
    [, , $admin] = userManagementContext();

    $this->actingAs($admin);

    $this->patch(route('admin.users.update', $admin), managedUserPayload($admin, [
        'role' => 'org-admin',
    ]))->assertForbidden();

    $this->delete(route('admin.users.destroy', $admin))->assertForbidden();

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('an org-admin cannot manage a peer org-admin', function () {
    [$org, $roles, $admin] = userManagementContext();
    $peer = managedUser($org, $roles['org-admin']);

    $this->actingAs($admin);

    $this->patch(route('admin.users.update', $peer), managedUserPayload($peer, [
        'role' => 'org-admin',
    ]))->assertForbidden();

    $this->delete(route('admin.users.destroy', $peer))->assertForbidden();

    expect(User::query()->whereKey($peer->id)->exists())->toBeTrue();
});

test('a non super-admin cannot touch a super-admin account', function () {
    [$org, $roles, $admin] = userManagementContext();
    $superAdmin = managedUser($org, $roles['super-admin']);

    $this->actingAs($admin);

    $this->patch(route('admin.users.update', $superAdmin), managedUserPayload($superAdmin, [
        'password' => 'NewPassword123!',
        'role' => 'supervisor',
    ]))->assertForbidden();

    expect(Hash::check('password', $superAdmin->fresh()->password))->toBeTrue();
});

test('a supervisor with update permission manages lower-ranked users only', function () {
    [$org, $roles] = userManagementContext();

    $supervisor = managedUser($org, $roles['supervisor']);
    $supervisor->givePermissionTo(['users.view', 'users.update', 'users.delete']);

    $teacher = managedUser($org, $roles['teacher']);
    $peerSupervisor = managedUser($org, $roles['supervisor']);

    $this->actingAs($supervisor);

    $this->patch(
        route('admin.users.update', $peerSupervisor),
        managedUserPayload($peerSupervisor, ['role' => 'supervisor']),
    )->assertForbidden();

    $this->patch(
        route('admin.users.update', $teacher),
        managedUserPayload($teacher, ['name' => 'معلم محدّث']),
    )->assertSessionHasNoErrors();

    expect($teacher->fresh()->name)->toBe('معلم محدّث');
});

test('an org-admin cannot touch a user of another organization', function () {
    [, , $admin] = userManagementContext();

    $otherOrg = Organization::factory()->create();
    $otherRoles = seedRolesForUserManagement($otherOrg->id);
    $foreignUser = managedUser($otherOrg, $otherRoles['teacher']);

    $this->actingAs($admin);

    $this->patch(
        route('admin.users.update', $foreignUser),
        managedUserPayload($foreignUser, ['role' => 'teacher']),
    )->assertForbidden();

    $this->delete(route('admin.users.destroy', $foreignUser))->assertForbidden();
});

test('the users directory never lists users of another organization', function () {
    [$org, $roles, $admin] = userManagementContext();
    managedUser($org, $roles['teacher']);

    $otherOrg = Organization::factory()->create();
    $otherRoles = seedRolesForUserManagement($otherOrg->id);
    managedUser($otherOrg, $otherRoles['teacher'], ['email' => 'foreign@example.com']);

    $this->actingAs($admin);

    $this->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 2)
            ->has('availableRoles', count(UserDirectoryQuery::ROLES))
            ->where('users.data.0.organization_id', $org->id)
            ->where('users.data.1.organization_id', $org->id));
});

test('deleting a lower-ranked user soft deletes it and is audited', function () {
    [$org, $roles, $admin] = userManagementContext();
    $teacher = managedUser($org, $roles['teacher']);

    $this->actingAs($admin);

    $this->delete(route('admin.users.destroy', $teacher))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::query()->whereKey($teacher->id)->exists())->toBeFalse()
        ->and(User::withTrashed()->whereKey($teacher->id)->exists())->toBeTrue();

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'user.deleted')
        ->where('auditable_id', (string) $teacher->id)
        ->exists())->toBeTrue();
});

test('guests and non-privileged roles cannot manage users', function () {
    [$org, $roles] = userManagementContext();

    $this->post(route('admin.users.store'), [])->assertRedirect('/login');

    $teacher = managedUser($org, $roles['teacher']);

    $this->actingAs($teacher)->get('/admin/users')->assertForbidden();
});

test('a super-admin manages org-admins but never their own account', function () {
    [$org, $roles] = userManagementContext();

    $superAdmin = managedUser($org, $roles['super-admin']);
    $orgAdmin = managedUser($org, $roles['org-admin']);

    $this->actingAs($superAdmin);

    $this->patch(route('admin.users.update', $orgAdmin), managedUserPayload($orgAdmin, [
        'name' => 'مدير محدّث',
        'role' => 'org-admin',
    ]))->assertSessionHasNoErrors();

    expect($orgAdmin->fresh()->name)->toBe('مدير محدّث');

    // نفس القيود تنطبق على super-admin: لا تعديل للذات عبر هذه الصفحة.
    $this->patch(route('admin.users.update', $superAdmin), managedUserPayload($superAdmin, [
        'role' => 'super-admin',
    ]))->assertForbidden();
});
