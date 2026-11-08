<?php

declare(strict_types=1);

use App\Http\Requests\UpdateSettingRequest;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRolesForSettings(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('settings update rejects arbitrary keys not present in the allow-list', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForSettings($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin);

    $this->patch('/admin/settings', [
        'settings' => [
            ['key' => 'malicious_unlisted_key', 'value' => 'anything', 'group' => null],
        ],
    ])->assertSessionHasErrors('settings.0.key');
});

test('settings update accepts allow-listed keys', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForSettings($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin);

    $this->patch('/admin/settings', [
        'settings' => [
            ['key' => UpdateSettingRequest::ALLOWED_KEYS[0], 'value' => 'مركز', 'group' => 'terminology'],
        ],
    ])->assertSessionHasNoErrors();

    expect(Setting::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->where('key', UpdateSettingRequest::ALLOWED_KEYS[0])
        ->value('value'))->toBe('مركز');
});
