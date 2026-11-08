<?php

declare(strict_types=1);

use App\Actions\UpdateFeatureFlagAction;
use App\Models\FeatureFlag;
use App\Models\Organization;
use App\Models\User;
use App\Support\FeatureFlagCache;
use Database\Seeders\FeatureFlagSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function seedRolesForFlags(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('the feature flag seeder enables the self-service flags for an organization', function () {
    $org = Organization::factory()->create();

    app(FeatureFlagSeeder::class)->seedForOrganization($org->id);

    $flags = FeatureFlag::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->pluck('enabled', 'key');

    expect($flags['certificates.enabled'])->toBeTrue()
        ->and($flags['advanced-reports.enabled'])->toBeTrue();
});

test('the feature flag seeder is idempotent and keeps an admin decision', function () {
    $org = Organization::factory()->create();

    $seeder = app(FeatureFlagSeeder::class);
    $seeder->seedForOrganization($org->id);

    FeatureFlag::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->where('key', 'certificates.enabled')
        ->update(['enabled' => false]);

    $seeder->seedForOrganization($org->id);

    expect(
        FeatureFlag::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('key', 'certificates.enabled')
            ->value('enabled')
    )->toBeFalse();
});

test('the certificates endpoint is blocked when the flag is disabled', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForFlags($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    FeatureFlag::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'key' => 'certificates.enabled',
        'enabled' => false,
    ]);

    $this->actingAs($admin)->get(route('certificates.index'))->assertForbidden();
});

test('the certificates endpoint is allowed when the flag is enabled', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForFlags($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    app(FeatureFlagSeeder::class)->seedForOrganization($org->id);

    $this->actingAs($admin)->get(route('certificates.index'))->assertOk();
});

test('UpdateFeatureFlagAction invalidates the cached flag immediately', function () {
    $org = Organization::factory()->create();
    seedRolesForFlags($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
    app(FeatureFlagSeeder::class)->seedForOrganization($org->id);

    $cache = app(FeatureFlagCache::class);

    // تعبئة الذاكرة أولاً (true) ثم تبديل العلم: بعد الإبطال يجب أن تُقرأ false فوراً.
    expect($cache->enabled($org->id, 'certificates.enabled'))->toBeTrue();

    $flag = FeatureFlag::withoutGlobalScopes()
        ->where('organization_id', $org->id)
        ->where('key', 'certificates.enabled')
        ->firstOrFail();

    $superAdmin = User::factory()->create(['organization_id' => $org->id]);

    app(UpdateFeatureFlagAction::class)->execute($flag, false, $superAdmin);

    expect($cache->enabled($org->id, 'certificates.enabled'))->toBeFalse();
});
