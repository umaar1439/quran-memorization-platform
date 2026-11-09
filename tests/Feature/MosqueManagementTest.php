<?php

declare(strict_types=1);

use App\Actions\CreateMosqueAction;
use App\DTOs\MosqueData;
use App\Enums\GenericStatus;
use App\Models\AuditLog;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيد خريطة role => Role.
 */
function seedRolesForMosqueManagement(int $organizationId): array
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
function mosqueManagementContext(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForMosqueManagement($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    return [$org, $roles, $admin];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function mosquePayload(array $overrides = []): array
{
    return [
        'name' => 'مسجد النور',
        'address' => 'حي السلام',
        'city' => 'الرياض',
        'phone' => '0551234567',
        'status' => 'active',
        ...$overrides,
    ];
}

test('an org-admin creates a mosque in their own organization and the creation is audited', function () {
    [$org, , $admin] = mosqueManagementContext();

    $this->actingAs($admin)
        ->post(route('admin.mosques.store'), mosquePayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mosque = Mosque::query()->where('name', 'مسجد النور')->firstOrFail();

    expect($mosque->organization_id)->toBe($org->id)
        ->and($mosque->city)->toBe('الرياض')
        ->and($mosque->status)->toBe(GenericStatus::Active);

    $audit = AuditLog::withoutGlobalScopes()
        ->where('event', 'mosque.created')
        ->where('auditable_id', (string) $mosque->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->organization_id)->toBe($org->id);
});

test('an org-admin updates a mosque and the change is audited', function () {
    [$org, , $admin] = mosqueManagementContext();
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($admin)
        ->patch(route('admin.mosques.update', $mosque->id), mosquePayload([
            'name' => 'مسجد الفرقان',
            'status' => 'inactive',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $mosque->refresh();

    expect($mosque->name)->toBe('مسجد الفرقان')
        ->and($mosque->status)->toBe(GenericStatus::Inactive);

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'mosque.updated')
        ->where('auditable_id', (string) $mosque->id)
        ->exists())->toBeTrue();
});

test('deleting a mosque soft deletes its circles and audits every cascade deletion', function () {
    [$org, , $admin] = mosqueManagementContext();

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $circles = Circle::factory()->count(2)->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.mosques.destroy', $mosque->id))
        ->assertRedirect();

    expect(Mosque::withTrashed()->find($mosque->id)?->trashed())->toBeTrue();

    foreach ($circles as $circle) {
        expect(Circle::withTrashed()->find($circle->id)?->trashed())->toBeTrue();

        expect(AuditLog::withoutGlobalScopes()
            ->where('event', 'circle.deleted')
            ->where('auditable_id', (string) $circle->id)
            ->exists())->toBeTrue();
    }

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'mosque.deleted')
        ->where('auditable_id', (string) $mosque->id)
        ->exists())->toBeTrue();
});

test('a mosque from another organization can neither be updated nor deleted (IDOR)', function () {
    [, , $admin] = mosqueManagementContext();

    $otherOrg = Organization::factory()->create();
    $foreignMosque = Mosque::factory()->create(['organization_id' => $otherOrg->id]);

    $this->actingAs($admin)
        ->patch(route('admin.mosques.update', $foreignMosque->id), mosquePayload())
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete(route('admin.mosques.destroy', $foreignMosque->id))
        ->assertNotFound();

    expect(Mosque::withoutGlobalScopes()->find($foreignMosque->id)?->trashed())->toBeFalse();
});

test('creating a mosque without an organization context is forbidden (fail-closed)', function () {
    $actor = User::factory()->create(['organization_id' => null]);

    // Regression: كان organization_id = null يصل إلى عمود NOT NULL فيُنتج 500.
    expect(fn () => app(CreateMosqueAction::class)->execute(
        MosqueData::fromArray(mosquePayload()),
        $actor,
    ))->toThrow(HttpException::class);

    expect(Mosque::withoutGlobalScopes()->count())->toBe(0);
});

test('the mosques page lists only the actor organization mosques', function () {
    [$org, , $admin] = mosqueManagementContext();

    Mosque::factory()->create(['organization_id' => $org->id, 'name' => 'مسجد منظمتي']);

    $otherOrg = Organization::factory()->create();
    Mosque::factory()->create(['organization_id' => $otherOrg->id, 'name' => 'مسجد منظمة أخرى']);

    $this->actingAs($admin)
        ->get(route('admin.mosques.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mosques/Index')
            ->has('mosques.data', 1)
            ->where('mosques.data.0.name', 'مسجد منظمتي')
            ->where('mosques.data.0.circles_count', 0));
});
