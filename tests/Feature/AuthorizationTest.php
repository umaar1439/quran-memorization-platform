<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\FeatureFlag;
use App\Models\Guardian;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRoles(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('teacher cannot record a recitation for a student outside their circle (IDOR)', function () {
    $org = Organization::factory()->create();
    $roles = seedRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $teacherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $otherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $teacherCircle->update(['teacher_id' => $teacher->id]);

    // الطالب المستهدف في حلقة أخرى لا يُدرّسها هذا المعلم.
    $outsideStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $otherCircle->id,
    ]);

    expect($teacher->can('create', [Recitation::class, $outsideStudent]))->toBeFalse();

    // بينما طالب في حلقته فعلاً مسموح.
    $ownStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $teacherCircle->id,
    ]);

    expect($teacher->can('create', [Recitation::class, $ownStudent]))->toBeTrue();
});

test('student cannot view another student profile', function () {
    $org = Organization::factory()->create();
    $roles = seedRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $studentA = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);
    $studentB = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $studentA->user->assignRole($roles['student']);
    $studentB->user->assignRole($roles['student']);

    expect($studentA->user->can('view', $studentB))->toBeFalse();
    expect($studentA->user->can('view', $studentA))->toBeTrue();
});

test('parent can only view their authorized children', function () {
    $org = Organization::factory()->create();
    $roles = seedRoles($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $authorizedChild = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);
    $unauthorizedChild = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $parentUser = User::factory()->create(['organization_id' => $org->id]);
    $parentUser->assignRole($roles['parent']);

    $guardian = Guardian::create([
        'organization_id' => $org->id,
        'user_id' => $parentUser->id,
        'name' => 'ولي أمر',
        'relationship_type' => 'أب',
    ]);

    $guardian->students()->attach($authorizedChild->id, [
        'organization_id' => $org->id,
        'permissions' => json_encode(['view_profile' => true, 'view_recitations' => true]),
    ]);

    expect($parentUser->can('view', $authorizedChild))->toBeTrue();
    expect($parentUser->can('view', $unauthorizedChild))->toBeFalse();
});

test('org admin cannot view mosque data from another organization (tenant isolation)', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $rolesA = seedRoles($orgA->id);
    seedRoles($orgB->id);

    $mosqueB = Mosque::factory()->create(['organization_id' => $orgB->id]);

    $adminA = User::factory()->create(['organization_id' => $orgA->id]);
    $adminA->assignRole($rolesA['org-admin']);

    expect($adminA->can('view', $mosqueB))->toBeFalse();

    // كذلك عبر Global Scope: لا يستطيع حتى استرجاع السجل.
    $this->actingAs($adminA);
    expect(Mosque::find($mosqueB->id))->toBeNull();
});

test('a user without permission cannot create a mosque', function () {
    $org = Organization::factory()->create();
    $roles = seedRoles($org->id);

    $student = User::factory()->create(['organization_id' => $org->id]);
    $student->assignRole($roles['student']);

    expect($student->can('create', Mosque::class))->toBeFalse();

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    expect($orgAdmin->can('create', Mosque::class))->toBeTrue();
});

test('disabled feature flag rejects the request with 403', function () {
    $org = Organization::factory()->create();
    seedRoles($org->id);

    FeatureFlag::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'key' => 'certificates.enabled',
        'enabled' => false,
    ]);

    $user = User::factory()->create(['organization_id' => $org->id]);

    Route::middleware(['web', 'feature:certificates.enabled'])
        ->get('/__test/certificates-feature', fn () => 'ok');

    $response = $this->actingAs($user)->get('/__test/certificates-feature');

    $response->assertStatus(403);
});
