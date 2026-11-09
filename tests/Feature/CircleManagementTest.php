<?php

declare(strict_types=1);

use App\Actions\CreateCircleAction;
use App\DTOs\CircleData;
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
function seedRolesForCircleManagement(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

/**
 * منظمة + أدوارها + مدير منظمة + مسجد + معلم داخل نفس المنظمة.
 *
 * @return array{0: Organization, 1: array<string, Role>, 2: User, 3: Mosque, 4: User}
 */
function circleManagementContext(): array
{
    $org = Organization::factory()->create();
    $roles = seedRolesForCircleManagement($org->id);

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id, 'name' => 'معلم المنظمة']);
    $teacher->assignRole($roles['teacher']);

    return [$org, $roles, $admin, $mosque, $teacher];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function circlePayload(Mosque $mosque, ?User $teacher, array $overrides = []): array
{
    return [
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher?->id,
        'name' => 'حلقة الفجر',
        'level' => 'متقدم',
        'capacity' => 25,
        'status' => 'active',
        ...$overrides,
    ];
}

test('an org-admin creates a circle in their own organization and the creation is audited', function () {
    [$org, , $admin, $mosque, $teacher] = circleManagementContext();

    $this->actingAs($admin)
        ->post(route('admin.circles.store'), circlePayload($mosque, $teacher))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $circle = Circle::query()->where('name', 'حلقة الفجر')->firstOrFail();

    expect($circle->organization_id)->toBe($org->id)
        ->and($circle->mosque_id)->toBe($mosque->id)
        ->and($circle->teacher_id)->toBe($teacher->id)
        ->and($circle->capacity)->toBe(25)
        ->and($circle->status)->toBe(GenericStatus::Active);

    $audit = AuditLog::withoutGlobalScopes()
        ->where('event', 'circle.created')
        ->where('auditable_id', (string) $circle->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->organization_id)->toBe($org->id);
});

test('a circle cannot reference a mosque or a teacher from another organization (IDOR)', function () {
    [, , $admin, $mosque, $teacher] = circleManagementContext();

    $otherOrg = Organization::factory()->create();
    $foreignMosque = Mosque::factory()->create(['organization_id' => $otherOrg->id]);
    $foreignTeacher = User::factory()->create(['organization_id' => $otherOrg->id]);

    $this->actingAs($admin)
        ->from(route('admin.circles.index'))
        ->post(route('admin.circles.store'), circlePayload($foreignMosque, $teacher))
        ->assertSessionHasErrors('mosque_id');

    $this->actingAs($admin)
        ->from(route('admin.circles.index'))
        ->post(route('admin.circles.store'), circlePayload($mosque, $foreignTeacher))
        ->assertSessionHasErrors('teacher_id');

    expect(Circle::query()->count())->toBe(0);
});

test('assigning a user without a teaching or supervisory role is rejected (422)', function () {
    [$org, $roles, $admin, $mosque] = circleManagementContext();

    $student = User::factory()->create(['organization_id' => $org->id]);
    $student->assignRole($roles['student']);

    $this->actingAs($admin)
        ->from(route('admin.circles.index'))
        ->post(route('admin.circles.store'), circlePayload($mosque, $student))
        ->assertSessionHasErrors('teacher_id');

    expect(Circle::query()->count())->toBe(0);
});

test('a supervisor can be assigned as a circle supervisor', function () {
    [$org, $roles, $admin, $mosque] = circleManagementContext();

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($admin)
        ->post(route('admin.circles.store'), circlePayload($mosque, $supervisor))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Circle::query()->where('teacher_id', $supervisor->id)->exists())->toBeTrue();
});

test('a soft-deleted mosque or teacher can no longer be assigned to a circle', function () {
    [, , $admin, $mosque, $teacher] = circleManagementContext();

    $this->actingAs($admin)
        ->from(route('admin.circles.index'))
        ->post(route('admin.circles.store'), circlePayload($mosque, $teacher))
        ->assertSessionHasNoErrors();

    // Regression: Rule::exists يتجاهل Soft Deletes فيقبل المحذوف.
    $mosque->delete();
    $teacher->delete();

    $this->actingAs($admin)
        ->from(route('admin.circles.index'))
        ->post(route('admin.circles.store'), circlePayload($mosque, $teacher, ['name' => 'حلقة على محذوف']))
        ->assertSessionHasErrors(['mosque_id', 'teacher_id']);

    expect(Circle::query()->where('name', 'حلقة على محذوف')->exists())->toBeFalse();
});

test('an org-admin updates a circle and the change is audited', function () {
    [$org, , $admin, $mosque, $teacher] = circleManagementContext();

    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.circles.update', $circle->id), circlePayload($mosque, $teacher, [
            'name' => 'حلقة العصر',
            'status' => 'inactive',
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $circle->refresh();

    expect($circle->name)->toBe('حلقة العصر')
        ->and($circle->teacher_id)->toBe($teacher->id)
        ->and($circle->status)->toBe(GenericStatus::Inactive);

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'circle.updated')
        ->where('auditable_id', (string) $circle->id)
        ->exists())->toBeTrue();
});

test('deleting a circle is audited', function () {
    [$org, , $admin, $mosque] = circleManagementContext();

    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.circles.destroy', $circle->id))
        ->assertRedirect();

    expect(Circle::withTrashed()->find($circle->id)?->trashed())->toBeTrue();

    expect(AuditLog::withoutGlobalScopes()
        ->where('event', 'circle.deleted')
        ->where('auditable_id', (string) $circle->id)
        ->exists())->toBeTrue();
});

test('a teacher without circles.manage cannot create or delete a circle', function () {
    [$org, $roles, , $mosque, $teacher] = circleManagementContext();

    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher->id,
    ]);

    $this->actingAs($teacher);

    $this->post(route('admin.circles.store'), circlePayload($mosque, $teacher, ['name' => 'حلقة غير مصرح بها']))
        ->assertForbidden();

    $this->delete(route('admin.circles.destroy', $circle->id))->assertForbidden();

    expect(Circle::query()->where('name', 'حلقة غير مصرح بها')->exists())->toBeFalse()
        ->and(Circle::find($circle->id))->not->toBeNull();
});

test('the circles page exposes only the actor organization mosques and teachers', function () {
    [$org, $roles, $admin, $mosque, $teacher] = circleManagementContext();

    // المشرف مؤهَّل أيضاً ليكون مسؤول حلقة، فيجب أن يظهر في القائمة.
    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $otherOrg = Organization::factory()->create();
    $otherRoles = seedRolesForCircleManagement($otherOrg->id);

    Mosque::factory()->create(['organization_id' => $otherOrg->id]);
    $foreignTeacher = User::factory()->create(['organization_id' => $otherOrg->id, 'name' => 'معلم منظمة أخرى']);
    $foreignTeacher->assignRole($otherRoles['teacher']);

    Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.circles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Circles/Index')
            ->has('circles.data', 1)
            ->has('mosques', 1)
            ->where('mosques.0.id', $mosque->id)
            ->has('teachers', 2)
            ->where('teachers', fn ($teachers) => $teachers->pluck('id')->sort()->values()->all()
                === collect([$teacher->id, $supervisor->id])->sort()->values()->all()));
});

test('creating a circle without an organization context is forbidden (fail-closed)', function () {
    $actor = User::factory()->create(['organization_id' => null]);

    expect(fn () => app(CreateCircleAction::class)->execute(
        CircleData::fromArray([
            'mosque_id' => 1,
            'teacher_id' => null,
            'name' => 'حلقة بلا سياق',
            'status' => 'active',
        ]),
        $actor,
    ))->toThrow(HttpException::class);

    expect(Circle::withoutGlobalScopes()->count())->toBe(0);
});
