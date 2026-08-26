<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRolesForRoleMiddleware(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('org-admin can access the admin dashboard after login (role middleware + tenant context)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleMiddleware($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();

    $this->get('/admin/dashboard')->assertOk();
});

test('a teacher cannot access the admin dashboard (role middleware denies)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleMiddleware($org->id);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $this->actingAs($teacher);

    $this->get('/admin/dashboard')->assertForbidden();
});

test('super-admin (team-scoped to their organization) can access admin routes through the full middleware stack', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleMiddleware($org->id);

    // super-admin يملك organization_id = المنظمة (وليس null) حتى يتطابق
    // team context الذي يضبطه ResolveTenant مع دور super-admin المقيّد بالمنظمة.
    $superAdmin = User::factory()->create([
        'organization_id' => $org->id,
        'email' => 'super-admin@example.com',
    ]);
    $superAdmin->assignRole($roles['super-admin']);

    $this->post('/login', [
        'email' => $superAdmin->email,
        'password' => 'password',
    ]);

    $this->get('/admin/circles')->assertOk();
});

test('org-admin can access the circles management page', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleMiddleware($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->get('/admin/circles')->assertOk();
});

test('supervisor can access the circles management page', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForRoleMiddleware($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->post('/login', [
        'email' => $supervisor->email,
        'password' => 'password',
    ]);

    $this->get('/admin/circles')->assertOk();
});
