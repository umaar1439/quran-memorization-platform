<?php

declare(strict_types=1);

use App\Enums\ExamResultStatus;
use App\Enums\ExamStatus;
use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;
use App\Models\Circle;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSection;
use App\Models\FeatureFlag;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\CertificateIssuedNotification;
use App\Notifications\ExamResultNotification;
use App\Notifications\GoalCompletionNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * ينشئ الأدوار والصلاحيات لمنظمة معينة ويعيدها كخريطة role => Role.
 */
function seedRolesForNotifications(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a student is notified when their exam result is approved', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForNotifications($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $studentUser = User::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'user_id' => $studentUser->id,
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'title' => 'اختبار تجريبي',
        'exam_type' => 'oral',
        'scheduled_at' => now(),
        'status' => ExamStatus::Scheduled,
        'created_by' => $orgAdmin->id,
    ]);

    $section = ExamSection::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'exam_id' => $exam->id,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'section_type' => 'recitation',
        'max_score' => 100,
    ]);

    $examResult = ExamResult::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'examiner_id' => $orgAdmin->id,
        'score' => 90,
        'status' => ExamResultStatus::Submitted,
    ]);

    $this->actingAs($orgAdmin);

    $this->postJson("/exam-results/{$examResult->id}/approve")->assertOk();

    Notification::assertSentTo($studentUser, ExamResultNotification::class);
});

test('a student is notified when their goal transitions to completed', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForNotifications($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $circle->update(['teacher_id' => $teacher->id]);

    $studentUser = User::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'user_id' => $studentUser->id,
    ]);

    $goal = StudentGoal::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'period_type' => PeriodType::Weekly,
        'target_type' => GoalTargetType::Memorization,
        'target_amount' => 50,
        'achieved_amount' => 50,
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'status' => GoalStatus::Active,
    ]);

    $this->actingAs($teacher);

    $this->patchJson("/student-goals/{$goal->id}", [
        'status' => GoalStatus::Completed->value,
    ])->assertOk();

    Notification::assertSentTo($studentUser, GoalCompletionNotification::class);
});

test('no duplicate goal completion notification is sent when updating an already-completed goal fails authorization', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForNotifications($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);
    $circle->update(['teacher_id' => $teacher->id]);

    $studentUser = User::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'user_id' => $studentUser->id,
    ]);

    $goal = StudentGoal::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'period_type' => PeriodType::Weekly,
        'target_type' => GoalTargetType::Memorization,
        'target_amount' => 50,
        'achieved_amount' => 50,
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'status' => GoalStatus::Completed,
    ]);

    $this->actingAs($teacher);

    // Policy يمنع تعديل هدف مُكتمل مسبقاً — لا إشعار يُرسل.
    $this->patchJson("/student-goals/{$goal->id}", [
        'achieved_amount' => 60,
    ])->assertForbidden();

    Notification::assertNothingSent();
});

test('a student is notified when a certificate is issued for them', function () {
    Notification::fake();
    Storage::fake('local');

    $org = Organization::factory()->create();
    $roles = seedRolesForNotifications($org->id);

    FeatureFlag::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'key' => 'certificates.enabled',
        'enabled' => true,
    ]);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $this->postJson('/certificates', [
        'student_id' => $student->id,
        'certificate_type' => 'إتمام حفظ',
    ])->assertStatus(202);

    Notification::assertSentTo($student->user, CertificateIssuedNotification::class);
});
