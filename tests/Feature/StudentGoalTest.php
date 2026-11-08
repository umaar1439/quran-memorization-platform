<?php

declare(strict_types=1);

use App\Actions\CalculateStudentAnalyticsAction;
use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;
use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SurahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SurahSeeder::class);
});

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForGoal(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a teacher creates a goal for a student in their circle successfully', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForGoal($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $circle->update(['teacher_id' => $teacher->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $this->actingAs($teacher);

    $response = $this->postJson('/student-goals', [
        'student_id' => $student->id,
        'period_type' => PeriodType::Weekly->value,
        'target_type' => GoalTargetType::Memorization->value,
        'surah_id' => 1,
        'target_amount' => 20,
        'period_start' => now()->toDateString(),
        'period_end' => now()->addWeek()->toDateString(),
    ]);

    $response->assertCreated();
    expect(StudentGoal::withoutGlobalScopes()->count())->toBe(1);

    $goal = StudentGoal::withoutGlobalScopes()->first();
    expect($goal->student_id)->toBe($student->id);
    expect($goal->organization_id)->toBe($org->id);
});

test('a teacher cannot create a goal for a student outside their circle (IDOR)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForGoal($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $teacherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $otherCircle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $teacherCircle->update(['teacher_id' => $teacher->id]);

    $outsideStudent = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $otherCircle->id,
    ]);

    $this->actingAs($teacher);

    $response = $this->postJson('/student-goals', [
        'student_id' => $outsideStudent->id,
        'period_type' => PeriodType::Weekly->value,
        'target_type' => GoalTargetType::Memorization->value,
        'surah_id' => 1,
        'target_amount' => 20,
        'period_start' => now()->toDateString(),
        'period_end' => now()->addWeek()->toDateString(),
    ]);

    $response->assertForbidden();
    expect(StudentGoal::withoutGlobalScopes()->count())->toBe(0);
});

test('analytics calculation reflects actual recitation data', function () {
    $org = Organization::factory()->create();
    seedRolesForGoal($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $circle->update(['teacher_id' => $teacher->id]);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    // سورة الفاتحة (7 آيات) — حفظ كامل بتقييم ممتاز 3 مرات لضمان اعتبارها نقطة قوة.
    for ($i = 0; $i < 3; $i++) {
        Recitation::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'mosque_id' => $mosque->id,
            'circle_id' => $circle->id,
            'type' => RecitationType::New,
            'surah_number' => 1,
            'ayah_from' => 1,
            'ayah_to' => 7,
            'rating' => RecitationRating::Excellent,
            'performed_at' => now()->subDays(3 - $i),
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    $action = app(CalculateStudentAnalyticsAction::class);
    $analytics = $action->execute($student);

    // سورة الفاتحة 7 آيات فريدة محفوظة من إجمالي 6236.
    expect($analytics['overall_progress']['memorized_ayahs'])->toBe(7);
    expect($analytics['overall_progress']['total_ayahs'])->toBe(6236);
    expect($analytics['overall_progress']['percentage'])->toBe(round(7 / 6236 * 100, 2));

    // نقطة قوة: سورة الفاتحة بتقييم ممتاز مستمر.
    $strengthSurahs = collect($analytics['strengths'])->pluck('surah_number');
    expect($strengthSurahs->contains(1))->toBeTrue();

    // معدل الحضور: الطالب سجّل تسميعاً في كل أيام نشاط الحلقة (100%).
    expect($analytics['attendance_rate']['percentage'])->toBe(100.0);
});

test('a goal cannot be updated after completion', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForGoal($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $goal = StudentGoal::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'period_type' => PeriodType::Weekly,
        'target_type' => GoalTargetType::Memorization,
        'surah_id' => 1,
        'target_amount' => 10,
        'period_start' => now()->toDateString(),
        'period_end' => now()->addWeek()->toDateString(),
        'status' => GoalStatus::Completed,
    ]);

    $this->actingAs($orgAdmin);

    $response = $this->patchJson("/student-goals/{$goal->id}", [
        'target_amount' => 50,
    ]);

    $response->assertForbidden();
});
