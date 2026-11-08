<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForAuditLog(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a successful login creates an auth.login audit log entry', function () {
    $org = Organization::factory()->create();
    seedRolesForAuditLog($org->id);

    $user = User::factory()->create([
        'organization_id' => $org->id,
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $log = AuditLog::withoutGlobalScopes()
        ->where('event', 'auth.login')
        ->where('user_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->organization_id)->toBe($org->id);
});

test('logging out creates an auth.logout audit log entry', function () {
    $org = Organization::factory()->create();
    seedRolesForAuditLog($org->id);

    $user = User::factory()->create(['organization_id' => $org->id]);

    $this->actingAs($user);
    $this->post('/logout')->assertRedirect();

    $log = AuditLog::withoutGlobalScopes()
        ->where('event', 'auth.logout')
        ->where('user_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
});

test('updating a user creates a user.updated audit log entry with old and new values, excluding password', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForAuditLog($org->id);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $orgAdmin->update(['name' => 'اسم محدَّث']);

    $log = AuditLog::withoutGlobalScopes()
        ->where('event', 'user.updated')
        ->where('auditable_id', $orgAdmin->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->new_values['name'])->toBe('اسم محدَّث');
    expect($log->new_values)->not->toHaveKey('password');
    expect($log->old_values)->not->toHaveKey('password');
});

test('org-admin can list audit logs scoped to their own organization only', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $rolesA = seedRolesForAuditLog($orgA->id);
    seedRolesForAuditLog($orgB->id);

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    AuditLog::withoutGlobalScopes()->create([
        'organization_id' => $orgA->id,
        'user_id' => $userA->id,
        'event' => 'user.updated',
        'auditable_type' => User::class,
        'auditable_id' => $userA->id,
        'old_values' => ['name' => 'قديم'],
        'new_values' => ['name' => 'جديد'],
        'request_id' => (string) Str::uuid(),
    ]);

    AuditLog::withoutGlobalScopes()->create([
        'organization_id' => $orgB->id,
        'user_id' => $userB->id,
        'event' => 'user.updated',
        'auditable_type' => User::class,
        'auditable_id' => $userB->id,
        'old_values' => ['name' => 'قديم'],
        'new_values' => ['name' => 'جديد'],
        'request_id' => (string) Str::uuid(),
    ]);

    $orgAdminA = User::factory()->create(['organization_id' => $orgA->id]);

    // إعادة ضبط سياق Team إلى orgA قبل تعيين الدور (seedRolesForAuditLog(orgB)
    // أعلاه غيّر السياق الحالي إلى orgB — انظر §6.2 من وثيقة التحقق المعماري).
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    $orgAdminA->assignRole($rolesA['org-admin']);

    $this->actingAs($orgAdminA);

    $response = $this->getJson('/audit-logs');

    $response->assertOk();
    // عقد الاستجابة الموحّد: ResourceCollection تُرجع {data: [...], links, meta}.
    $ids = collect($response->json('data'))->pluck('organization_id')->unique();

    expect($ids->count())->toBe(1);
    expect($ids->first())->toBe($orgA->id);
});

test('a teacher without org-admin role cannot list audit logs', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForAuditLog($org->id);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $this->actingAs($teacher);

    $this->getJson('/audit-logs')->assertForbidden();
});

test('audit logs are never updated or deleted (append-only) — no update/delete route exists', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForAuditLog($org->id);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $log = AuditLog::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'user_id' => $orgAdmin->id,
        'event' => 'user.updated',
        'auditable_type' => User::class,
        'auditable_id' => $orgAdmin->id,
        'old_values' => ['name' => 'قديم'],
        'new_values' => ['name' => 'جديد'],
        'request_id' => (string) Str::uuid(),
    ]);

    $this->actingAs($orgAdmin);

    $this->patchJson("/audit-logs/{$log->id}", ['event' => 'tampered'])->assertNotFound();
    $this->deleteJson("/audit-logs/{$log->id}")->assertNotFound();

    expect(AuditLog::withoutGlobalScopes()->find($log->id)->event)->toBe('user.updated');
});
