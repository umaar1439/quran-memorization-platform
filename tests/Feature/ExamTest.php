<?php

declare(strict_types=1);

use App\Enums\ExamResultStatus;
use App\Enums\ExamStatus;
use App\Models\AuditLog;
use App\Models\Circle;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSection;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ExamResultNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function seedRolesForExam(int $organizationId): array
{
    $seeder = new RoleAndPermissionSeeder;
    $seeder->run();

    return $seeder->createRolesForOrganization($organizationId);
}

test('a supervisor creates an exam with sections successfully', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor);

    $response = $this->postJson('/exams', [
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'title' => 'امتحان نصف الفصل',
        'exam_type' => 'oral',
        'scheduled_at' => now()->addDays(3)->toDateTimeString(),
        'sections' => [
            ['surah_number' => 1, 'ayah_from' => 1, 'ayah_to' => 7, 'section_type' => 'recitation', 'max_score' => 100],
        ],
    ]);

    $response->assertCreated();

    $exam = Exam::withoutGlobalScopes()->first();
    expect($exam->status)->toBe(ExamStatus::Draft);
    expect(ExamSection::withoutGlobalScopes()->where('exam_id', $exam->id)->count())->toBe(1);
});

test('a teacher cannot create an exam (missing exams.create permission)', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $teacher = User::factory()->create(['organization_id' => $org->id]);
    $teacher->assignRole($roles['teacher']);

    $this->actingAs($teacher);

    $response = $this->postJson('/exams', [
        'title' => 'امتحان',
        'exam_type' => 'oral',
    ]);

    $response->assertForbidden();
});

test('an org-admin approves an exam result and it becomes immutable', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'title' => 'امتحان',
        'exam_type' => 'oral',
        'status' => ExamStatus::Completed,
        'created_by' => User::factory()->create(['organization_id' => $org->id])->id,
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

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $this->actingAs($orgAdmin);

    $storeResponse = $this->postJson('/exam-results', [
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'student_id' => $student->id,
        'score' => 90,
        'grade' => 'ممتاز',
        'status' => 'submitted',
    ]);
    $storeResponse->assertCreated();

    $resultId = $storeResponse->json('data.id');

    $approveResponse = $this->postJson("/exam-results/{$resultId}/approve");
    $approveResponse->assertOk();

    $result = ExamResult::withoutGlobalScopes()->find($resultId);
    expect($result->status)->toBe(ExamResultStatus::Approved);
    expect($result->isApproved())->toBeTrue();

    // بعد الاعتماد: لا يمكن التعديل المباشر عبر Policy.
    expect($orgAdmin->can('update', $result))->toBeFalse();
});

test('correction workflow updates score and creates an audit log with old and new values', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'title' => 'امتحان',
        'exam_type' => 'oral',
        'status' => ExamStatus::Completed,
        'created_by' => User::factory()->create(['organization_id' => $org->id])->id,
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

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $result = ExamResult::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'student_id' => $student->id,
        'score' => 70,
        'grade' => 'جيد',
        'examiner_id' => $orgAdmin->id,
        'status' => ExamResultStatus::Approved,
        'approved_by' => $orgAdmin->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($orgAdmin);

    $response = $this->postJson("/exam-results/{$result->id}/correct", [
        'score' => 85,
        'grade' => 'ممتاز',
        'reason' => 'خطأ في تسجيل الدرجة الأصلية',
    ]);

    $response->assertOk();

    $result->refresh();
    expect($result->score)->toBe(85);
    expect($result->grade)->toBe('ممتاز');

    $auditLog = AuditLog::withoutGlobalScopes()
        ->where('event', 'exam_result.corrected')
        ->where('auditable_id', $result->id)
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->old_values['score'])->toBe(70);
    expect($auditLog->new_values['score'])->toBe(85);
    expect($auditLog->new_values['reason'])->toBe('خطأ في تسجيل الدرجة الأصلية');
});

test('creating an exam with an unsupported section type is rejected and nothing is persisted', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $this->actingAs($supervisor);

    $response = $this->postJson('/exams', [
        'title' => 'امتحان',
        'exam_type' => 'oral',
        'sections' => [
            ['surah_number' => 1, 'ayah_from' => 1, 'ayah_to' => 7, 'section_type' => 'writing', 'max_score' => 100],
        ],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('sections.0.section_type');

    // لا امتحان ولا قسم: التحقق يسبق المعاملة، فلا شيء يُحفظ جزئياً.
    expect(Exam::withoutGlobalScopes()->count())->toBe(0)
        ->and(ExamSection::withoutGlobalScopes()->count())->toBe(0);
});

test('updating an exam writes only the submitted fields and can clear the schedule explicitly', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole($roles['supervisor']);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'title' => 'الاسم القديم',
        'exam_type' => 'oral',
        'scheduled_at' => '2026-01-05 09:00:00',
        'status' => ExamStatus::Draft,
        'created_by' => $supervisor->id,
    ]);

    $this->actingAs($supervisor);

    $this->patchJson("/exams/{$exam->id}", ['title' => 'الاسم الجديد'])->assertOk();

    $exam->refresh();
    expect($exam->title)->toBe('الاسم الجديد')
        ->and($exam->exam_type)->toBe('oral')
        ->and($exam->scheduled_at?->toDateTimeString())->toBe('2026-01-05 09:00:00');

    // null صريح يمسح التاريخ — بخلاف غياب الحقل الذي يُبقيه.
    $this->patchJson("/exams/{$exam->id}", ['scheduled_at' => null])->assertOk();

    expect($exam->refresh()->scheduled_at)->toBeNull()
        ->and($exam->title)->toBe('الاسم الجديد');
});

test('approving a result notifies the student once and stays idempotent on retry', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
    ]);

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'title' => 'امتحان',
        'exam_type' => 'oral',
        'status' => ExamStatus::Completed,
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

    $result = ExamResult::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'student_id' => $student->id,
        'score' => 90,
        'grade' => 'ممتاز',
        'examiner_id' => $orgAdmin->id,
        'status' => ExamResultStatus::Submitted,
    ]);

    $this->actingAs($orgAdmin);

    $this->postJson("/exam-results/{$result->id}/approve")->assertOk();
    $this->postJson("/exam-results/{$result->id}/approve")->assertOk();

    expect($result->refresh()->status)->toBe(ExamResultStatus::Approved);

    Notification::assertSentToTimes($student->user, ExamResultNotification::class, 1);
});

test('correction workflow rejects correction on a non-approved result', function () {
    $org = Organization::factory()->create();
    $roles = seedRolesForExam($org->id);

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $student = StudentProfile::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);

    $exam = Exam::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'title' => 'امتحان',
        'exam_type' => 'oral',
        'status' => ExamStatus::Completed,
        'created_by' => User::factory()->create(['organization_id' => $org->id])->id,
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

    $orgAdmin = User::factory()->create(['organization_id' => $org->id]);
    $orgAdmin->assignRole($roles['org-admin']);

    $result = ExamResult::withoutGlobalScopes()->create([
        'organization_id' => $org->id,
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'student_id' => $student->id,
        'score' => 70,
        'examiner_id' => $orgAdmin->id,
        'status' => ExamResultStatus::Submitted,
    ]);

    $this->actingAs($orgAdmin);

    $response = $this->postJson("/exam-results/{$result->id}/correct", [
        'score' => 85,
        'reason' => 'تجربة',
    ]);

    $response->assertStatus(422);
});
