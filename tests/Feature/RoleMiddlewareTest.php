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
