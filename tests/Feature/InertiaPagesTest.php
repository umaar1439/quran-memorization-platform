<?php

declare(strict_types=1);

use App\Models\Circle;
use App\Models\Exam;
use App\Models\FeatureFlag;
use App\Models\Guardian;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * صفحات Inertia التي نُقلت استعلاماتها إلى app/Queries: نتحقق من أن كل صفحة
 * لا تزال تُعيد 200 مع المكوّن والخصائص المتوقعة، حتى لا يكسر إعادة الهيكلة
 * (Refactor) العقد بين الـ Controller والواجهة.
 */
function seedRolesForPages(Organization $organization): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organization->id);
}

/**
 * @return array{organization: Organization, roles: array<string, mixed>}
 */
function pagesOrganization(): array
{
    $organization = Organization::factory()->create();

    return [
        'organization' => $organization,
        'roles' => seedRolesForPages($organization),
    ];
}

test('the feature flags page lists only self-service flags for an org-admin', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    FeatureFlag::create(['organization_id' => $org->id, 'key' => 'certificates.enabled', 'enabled' => true]);
    FeatureFlag::create(['organization_id' => $org->id, 'key' => 'advanced-reports.enabled', 'enabled' => false]);
    // علم غير مسموح لمدير المنظمة (ليس ضمن SELF_SERVICE_FLAGS) يجب ألا يظهر.
    FeatureFlag::create(['organization_id' => $org->id, 'key' => 'internal.beta', 'enabled' => true]);
    // العلم العالمي لا يمكن لمدير المنظمة تبديله (Policy)، فلا يُعرض له.
    FeatureFlag::create(['organization_id' => null, 'key' => 'certificates.enabled', 'enabled' => false]);

    $this->actingAs($admin)
        ->get('/admin/feature-flags')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/FeatureFlags/Index')
            ->has('flags', 2)
            ->where('flags.0.key', 'advanced-reports.enabled')
            ->where('flags.1.key', 'certificates.enabled')
            ->where('isSuperAdmin', false));
});

test('the feature flags page lists every flag for a super-admin', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    // super-admin يملك organization_id = المنظمة (وليس null) ليطابق team context
    // الذي يضبطه ResolveTenant مع دوره المقيّد بالمنظمة.
    $superAdmin = User::factory()->create([
        'organization_id' => $org->id,
        'email' => 'super-admin@example.com',
    ]);
    $superAdmin->assignRole($roles['super-admin']);

    FeatureFlag::create(['organization_id' => $org->id, 'key' => 'certificates.enabled', 'enabled' => true]);
    FeatureFlag::create(['organization_id' => $org->id, 'key' => 'internal.beta', 'enabled' => true]);
    FeatureFlag::create(['organization_id' => null, 'key' => 'certificates.enabled', 'enabled' => false]);

    $this->actingAs($superAdmin)
        ->get('/admin/feature-flags')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/FeatureFlags/Index')
            ->has('flags', 3)
            ->where('isSuperAdmin', true));
});

test('the student transfers page renders with mosque and student options', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/student-transfers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/StudentTransfers/Index')
            ->has('transfers.data', 0)
            ->has('mosques', 1)
            ->has('students', 1));
});

test('the exam results page renders through the exam result query', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole($roles['org-admin']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $exam = Exam::create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'title' => 'اختبار تجريبي',
        'exam_type' => 'periodic',
        'status' => 'completed',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get("/admin/exams/{$exam->id}/results")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Exams/Results')
            ->where('exam.id', $exam->id)
            ->has('results.data', 0));
});

test('the teacher my-circle page lists only the circles of the current teacher', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'teacher_id' => $teacher->id,
    ]);
    // حلقة معلم آخر في نفس المنظمة يجب ألا تظهر.
    Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $this->actingAs($teacher)
        ->get('/teacher/my-circle')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/MyCircle')
            ->has('circles', 1)
            ->where('circles.0.id', $circle->id));
});

test('the parent dashboard page summarizes the linked children only', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $parent = User::factory()->create(['organization_id' => $org->id]);
    $parent->assignRole($roles['parent']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $child = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $guardian = Guardian::create([
        'organization_id' => $org->id,
        'user_id' => $parent->id,
        'name' => 'ولي أمر تجريبي',
        'relationship_type' => 'أب',
    ]);

    $guardian->students()->attach($child->id, [
        'organization_id' => $org->id,
        'permissions' => json_encode(['view_recitations' => true]),
    ]);

    $this->actingAs($parent)
        ->get('/parent/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Parent/Dashboard')
            ->has('children', 1)
            ->where('children.0.student.id', $child->id));
});

test('the student goals and exams pages render the current student data', function () {
    ['organization' => $org, 'roles' => $roles] = pagesOrganization();

    $studentUser = User::factory()->create(['organization_id' => $org->id]);
    $studentUser->assignRole($roles['student']);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    StudentProfile::factory()->create([
        'user_id' => $studentUser->id,
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $this->actingAs($studentUser)
        ->get('/student/goals')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Goals')
            ->has('goals.data', 0));

    $this->actingAs($studentUser)
        ->get('/student/exams')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Exams')
            ->has('results.data', 0));
});
