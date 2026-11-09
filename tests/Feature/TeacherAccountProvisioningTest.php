<?php

declare(strict_types=1);

use App\Actions\CreateTeacherAction;
use App\Actions\DeleteTeacherAction;
use App\Actions\UpdateTeacherAction;
use App\DTOs\TeacherData;
use App\Enums\UserStatus;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\TeacherProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function seedRolesForProvisioning(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * انحدار (Regression): كان User::create(['organization_id' => ...]) يتجاهل
 * الحقل صامتاً لأن organization_id محمي من التعبئة الجماعية، فتخرج حسابات
 * المعلمين/الطلاب بلا منظمة — أي بلا عزل مستأجرين إطلاقاً.
 */
test('a teacher created through the admin endpoint belongs to the admin organization', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForProvisioning($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->post(route('admin.teachers.store'), [
            'name' => 'معلم اختبار',
            'email' => 'provisioned-teacher@example.com',
            'password' => 'password',
            'phone' => '0500000000',
            'status' => 'inactive',
            'circles' => [$circle->id],
        ])
        ->assertRedirect();

    $teacher = User::withoutGlobalScopes()->where('email', 'provisioned-teacher@example.com')->firstOrFail();

    expect($teacher->organization_id)->toBe($org->id)
        ->and($teacher->status)->toBe(UserStatus::Inactive)
        ->and($teacher->phone)->toBe('0500000000')
        ->and($teacher->hasRole('teacher'))->toBeTrue()
        ->and($teacher->teacherProfile?->organization_id)->toBe($org->id);
});

test('a student created through the admin endpoint belongs to the admin organization', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForProvisioning($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $otherMosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $this->actingAs($admin)
        ->post(route('admin.students.store'), [
            'name' => 'طالب اختبار',
            'email' => 'provisioned-student@example.com',
            'password' => 'password',
            'enrollment_number' => 'STU-REG-001',
            'circle_id' => $circle->id,
            'status' => 'active',
            // محاولة تثبيت مسجد مختلف: يجب أن يُشتق mosque_id من الحلقة لا من الطلب.
            'mosque_id' => $otherMosque->id,
        ])
        ->assertRedirect();

    $studentUser = User::withoutGlobalScopes()->where('email', 'provisioned-student@example.com')->firstOrFail();
    $profile = $studentUser->studentProfile;

    expect($studentUser->organization_id)->toBe($org->id)
        ->and($profile)->not->toBeNull()
        ->and($profile->circle_id)->toBe($circle->id)
        ->and($profile->mosque_id)->toBe($circle->mosque_id);
});

test('CreateTeacherAction creates the account with an explicit organization and synced circles', function () {
    $org = Organization::factory()->create();
    seedRolesForProvisioning($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);

    $profile = app(CreateTeacherAction::class)->execute(
        TeacherData::fromArray([
            'name' => 'معلم بالـ Action',
            'email' => 'action-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [$circle->id],
        ]),
        $admin,
    );

    expect($profile->organization_id)->toBe($org->id)
        ->and($profile->user?->organization_id)->toBe($org->id)
        ->and($circle->fresh()?->teacher_id)->toBe($profile->user_id);
});

test('UpdateTeacherAction moves circle links without changing the organization', function () {
    $org = Organization::factory()->create();
    seedRolesForProvisioning($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circleA = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $circleB = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);

    $profile = app(CreateTeacherAction::class)->execute(
        TeacherData::fromArray([
            'name' => 'معلم للنقل',
            'email' => 'move-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [$circleA->id],
        ]),
        $admin,
    );

    app(UpdateTeacherAction::class)->execute(
        $profile,
        TeacherData::fromArray([
            'name' => 'معلم للنقل بعد التعديل',
            'email' => 'move-teacher@example.com',
            'status' => 'active',
            'circles' => [$circleB->id],
        ]),
    );

    expect($circleA->fresh()?->teacher_id)->toBeNull()
        ->and($circleB->fresh()?->teacher_id)->toBe($profile->user_id)
        ->and($profile->fresh()?->organization_id)->toBe($org->id);
});

test('DeleteTeacherAction unlinks circles and soft deletes the account', function () {
    $org = Organization::factory()->create();
    seedRolesForProvisioning($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $admin = User::factory()->create(['organization_id' => $org->id]);

    $profile = app(CreateTeacherAction::class)->execute(
        TeacherData::fromArray([
            'name' => 'معلم للحذف',
            'email' => 'delete-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [$circle->id],
        ]),
        $admin,
    );

    $userId = $profile->user_id;

    app(DeleteTeacherAction::class)->execute($profile);

    expect($circle->fresh()?->teacher_id)->toBeNull()
        ->and(TeacherProfile::query()->find($profile->id))->toBeNull()
        ->and(User::query()->find($userId))->toBeNull()
        ->and(User::withTrashed()->withoutGlobalScopes()->find($userId))->not->toBeNull();
});

/**
 * P0 (Fail-Closed): مسؤول بلا organization_id كان يُنشئ معلماً بـ
 * organization_id = null/0 فيفلت الحساب من كل عزل مستأجرين. الـ Policy ترفض
 * الإنشاء، والـ Action يرمي 403 كحارس ثانٍ.
 */
test('teacher creation is fail-closed when the actor has no organization', function () {
    $org = Organization::factory()->create();
    seedRolesForProvisioning($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $orgAdminWithoutOrg = User::factory()->create(['organization_id' => null]);
    $orgAdminWithoutOrg->assignRole('org-admin');

    // الصلاحية موجودة، لكن غياب المنظمة وحده يكفي لرفض الإنشاء.
    expect($orgAdminWithoutOrg->can('create', TeacherProfile::class))->toBeFalse();
});

test('CreateTeacherAction aborts with 403 instead of writing an orphan teacher account', function () {
    $org = Organization::factory()->create();
    seedRolesForProvisioning($org->id);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdminWithoutOrg = User::factory()->create(['organization_id' => null]);

    expect(fn () => app(CreateTeacherAction::class)->execute(
        TeacherData::fromArray([
            'name' => 'معلم بلا منظمة',
            'email' => 'orphan-teacher@example.com',
            'password' => 'password',
            'status' => 'active',
            'circles' => [$circle->id],
        ]),
        $orgAdminWithoutOrg,
    ))->toThrow(HttpException::class);

    expect(User::withoutGlobalScopes()->where('email', 'orphan-teacher@example.com')->exists())->toBeFalse()
        ->and(TeacherProfile::query()->where('user_id', $orgAdminWithoutOrg->id)->exists())->toBeFalse();
});
