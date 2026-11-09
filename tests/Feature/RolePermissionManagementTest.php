<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function seedRolesForRoleManagement(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * @return array{0: Organization, 1: array<string, Role>, 2: User}
 */
function organizationWithOrgAdmin(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleManagement($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    return [$org, $roles, $admin];
}

test('org-admin can open the roles page and sees every role of their organization', function () {
    [, , $admin] = organizationWithOrgAdmin();

    $this->actingAs($admin);

    $this->get('/admin/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles', count(RoleAndPermissionSeeder::ROLES))
            ->has('permissions', count(RoleAndPermissionSeeder::PERMISSIONS))
            ->where('roles.0.name', 'super-admin')
            ->where('roles.0.editable', false)
            ->where('roles.1.name', 'org-admin')
            ->where('roles.1.editable', true));
});

test('a supervisor without roles.view cannot open the roles page', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleManagement($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor);

    $this->get('/admin/roles')->assertForbidden();
});

test('org-admin can grant a permission to another role and the change is audited', function () {
    [$org, $roles, $admin] = organizationWithOrgAdmin();

    $permissions = $roles['supervisor']->permissions()->pluck('name')->all();
    $permissions[] = 'certificates.generate';

    $this->actingAs($admin);

    $this->patch(route('admin.roles.update', $roles['supervisor']), [
        'permissions' => $permissions,
    ])->assertSessionHasNoErrors();

    expect($roles['supervisor']->fresh()->permissions()->pluck('name')->all())
        ->toContain('certificates.generate');

    expect(AuditLog::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->where('event', 'role.permissions_updated')
        ->count())->toBe(1);
});

test('org-admin can revoke a permission from another role', function () {
    [, $roles, $admin] = organizationWithOrgAdmin();

    $permissions = array_values(array_diff(
        $roles['teacher']->permissions()->pluck('name')->all(),
        ['exams.view'],
    ));

    $this->actingAs($admin);

    $this->patch(route('admin.roles.update', $roles['teacher']), [
        'permissions' => $permissions,
    ])->assertSessionHasNoErrors();

    expect($roles['teacher']->fresh()->permissions()->pluck('name')->all())
        ->not->toContain('exams.view');
});

test('the super-admin role is protected from the UI even for org-admin', function () {
    [, $roles, $admin] = organizationWithOrgAdmin();

    $before = $roles['super-admin']->permissions()->pluck('name')->all();

    $this->actingAs($admin);

    $this->patch(route('admin.roles.update', $roles['super-admin']), [
        'permissions' => ['circles.view'],
    ])->assertForbidden();

    expect($roles['super-admin']->fresh()->permissions()->pluck('name')->all())->toBe($before);
});

test('a role manager cannot grant a permission they do not hold', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleManagement($org->id);

    // دور مخصص يملك إدارة الأدوار فقط — منحه صلاحية لا يملكها = تصعيد صلاحيات.
    $limitedRole = Role::findOrCreate('limited-admin', 'web');
    $limitedRole->syncPermissions(['roles.view', 'roles.manage']);

    $limited = User::factory()->create(['organization_id' => $org->id]);
    $limited->assignRole($limitedRole);

    $permissions = $roles['supervisor']->permissions()->pluck('name')->all();
    $permissions[] = 'certificates.generate';

    $this->actingAs($limited);

    $this->from('/admin/roles')
        ->patch(route('admin.roles.update', $roles['supervisor']), [
            'permissions' => $permissions,
        ])
        ->assertSessionHasErrors('permissions');

    expect($roles['supervisor']->fresh()->permissions()->pluck('name')->all())
        ->not->toContain('certificates.generate');
});

test('a role manager cannot remove roles.manage from a role they hold', function () {
    [, $roles, $admin] = organizationWithOrgAdmin();

    $permissions = array_values(array_diff(
        $roles['org-admin']->permissions()->pluck('name')->all(),
        ['roles.manage'],
    ));

    $this->actingAs($admin);

    $this->from('/admin/roles')
        ->patch(route('admin.roles.update', $roles['org-admin']), [
            'permissions' => $permissions,
        ])
        ->assertSessionHasErrors('permissions');

    expect($roles['org-admin']->fresh()->permissions()->pluck('name')->all())
        ->toContain('roles.manage');
});

test('an org-admin cannot modify a role that belongs to another organization', function () {
    [, , $admin] = organizationWithOrgAdmin();

    $otherOrg = Organization::factory()->create();
    $otherRoles = seedRolesForRoleManagement($otherOrg->id);

    $this->actingAs($admin);

    $this->patch(route('admin.roles.update', $otherRoles['supervisor']), [
        'permissions' => ['circles.view'],
    ])->assertForbidden();
});

test('unknown permission names are rejected by validation', function () {
    [, $roles, $admin] = organizationWithOrgAdmin();

    $this->actingAs($admin);

    $this->from('/admin/roles')
        ->patch(route('admin.roles.update', $roles['supervisor']), [
            'permissions' => ['not.a.real.permission'],
        ])
        ->assertSessionHasErrors('permissions.0');
});
