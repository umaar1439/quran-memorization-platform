<?php

use App\Enums\ExamResultStatus;
use App\Models\Circle;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamSection;
use App\Models\Guardian;
use App\Models\Mosque;
use App\Models\Organization;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\Surah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all seed factories create valid, persisted records', function () {
    $org = Organization::factory()->create();
    expect($org->exists)->toBeTrue();

    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    expect($mosque->organization_id)->toBe($org->id);

    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    expect($circle->mosque_id)->toBe($mosque->id);

    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);
    expect($student->user)->not->toBeNull();

    $recitation = Recitation::factory()->create([
        'organization_id' => $org->id,
        'student_id' => $student->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);
    expect($recitation->student_id)->toBe($student->id);
});

test('a student belongs to a mosque and a circle', function () {
    $org = Organization::factory()->create();
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    expect($student->mosque)->toBeInstanceOf(Mosque::class);
    expect($student->circle)->toBeInstanceOf(Circle::class);
    expect($circle->studentProfiles)->toHaveCount(1);
});

test('a guardian can be linked to a student with granular permissions', function () {
    $org = Organization::factory()->create();
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $guardian = Guardian::create([
        'organization_id' => $org->id,
        'name' => 'ولي أمر تجريبي',
        'relationship_type' => 'أب',
    ]);

    $guardian->students()->attach($student->id, [
        'organization_id' => $org->id,
        'permissions' => json_encode(['view_recitations' => true]),
    ]);

    expect($guardian->students)->toHaveCount(1);
    expect($student->guardians)->toHaveCount(1);
    expect(json_decode($guardian->students->first()->pivot->permissions, true))
        ->toBe(['view_recitations' => true]);
});

test('the surahs reference table contains exactly 114 surahs after seeding', function () {
    $this->seed(\Database\Seeders\SurahSeeder::class);

    expect(Surah::count())->toBe(114);
    expect(Surah::sum('ayah_count'))->toBe(6236);
});

test('an approved exam result is flagged immutable via isApproved', function () {
    $org = Organization::factory()->create();
    $examiner = User::factory()->create(['organization_id' => $org->id]);
    $mosque = Mosque::factory()->create(['organization_id' => $org->id]);
    $circle = Circle::factory()->create(['organization_id' => $org->id, 'mosque_id' => $mosque->id]);
    $student = StudentProfile::factory()->create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
    ]);

    $exam = Exam::create([
        'organization_id' => $org->id,
        'mosque_id' => $mosque->id,
        'circle_id' => $circle->id,
        'title' => 'اختبار الجزء الأول',
        'exam_type' => 'periodic',
        'status' => 'completed',
        'created_by' => $examiner->id,
    ]);

    $section = ExamSection::create([
        'exam_id' => $exam->id,
        'organization_id' => $org->id,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'section_type' => 'recitation',
        'max_score' => 100,
    ]);

    $result = ExamResult::create([
        'exam_id' => $exam->id,
        'exam_section_id' => $section->id,
        'student_id' => $student->id,
        'organization_id' => $org->id,
        'score' => 95,
        'examiner_id' => $examiner->id,
        'status' => ExamResultStatus::Approved,
        'approved_by' => $examiner->id,
        'approved_at' => now(),
    ]);

    expect($result->isApproved())->toBeTrue();
});
