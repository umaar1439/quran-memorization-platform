<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsCache;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRolesForBulkSettings(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('the settings seeder creates the default terminology values', function () {
    $org = Organization::factory()->create();

    app(SettingSeeder::class)->seedForOrganization($org->id);

    expect(
        Setting::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->pluck('value', 'key')
            ->all()
    )->toMatchArray([
        'circle_label' => 'حلقة',
        'mosque_label' => 'مسجد',
        'exam_pass_threshold' => '60',
    ]);
});

test('a settings update persists every submitted key in a single request', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForBulkSettings($org->id);
    app(SettingSeeder::class)->seedForOrganization($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->patch(route('admin.settings.update'), [
            'settings' => [
                ['key' => 'circle_label', 'value' => 'مجلس', 'group' => 'terminology'],
                ['key' => 'mosque_label', 'value' => 'جامع', 'group' => 'terminology'],
                ['key' => 'exam_pass_threshold', 'value' => '70', 'group' => 'exams'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(
        Setting::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->pluck('value', 'key')
            ->all()
    )->toMatchArray([
        'circle_label' => 'مجلس',
        'mosque_label' => 'جامع',
        'exam_pass_threshold' => '70',
    ]);
});

test('the settings cache reflects a saved value immediately', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForBulkSettings($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $cache = app(SettingsCache::class);

    expect($cache->all($org->id))->toBe([]);

    $this->actingAs($admin)
        ->patch(route('admin.settings.update'), [
            'settings' => [
                ['key' => 'default_locale', 'value' => 'en', 'group' => 'localization'],
            ],
        ])
        ->assertRedirect();

    expect($cache->all($org->id)['default_locale'] ?? null)->toBe('en');
});
