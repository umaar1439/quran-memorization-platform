<?php

declare(strict_types=1);

use App\DTOs\ExamCorrectionData;
use App\DTOs\ExamResultData;
use App\DTOs\ExamSectionData;
use App\DTOs\ExamSubmissionData;
use App\DTOs\ExamUpdateData;
use App\Enums\ExamResultStatus;
use App\Enums\ExamSectionType;
use App\Enums\ExamStatus;

test('ExamSubmissionData maps a validated payload with its nested sections', function () {
    $dto = ExamSubmissionData::fromArray([
        'mosque_id' => '4',
        'circle_id' => '9',
        'title' => 'امتحان نصف الفصل',
        'exam_type' => 'oral',
        'scheduled_at' => '2026-01-05 09:00:00',
        'sections' => [
            [
                'surah_number' => '1',
                'ayah_from' => '1',
                'ayah_to' => '7',
                'section_type' => 'recitation',
                'max_score' => '100',
            ],
        ],
    ]);

    expect($dto->mosqueId)->toBe(4)
        ->and($dto->circleId)->toBe(9)
        ->and($dto->title)->toBe('امتحان نصف الفصل')
        ->and($dto->examType)->toBe('oral')
        ->and($dto->scheduledAt)->toBe('2026-01-05 09:00:00')
        ->and($dto->sections)->toHaveCount(1)
        ->and($dto->sections[0])->toBeInstanceOf(ExamSectionData::class)
        ->and($dto->sections[0]->surahNumber)->toBe(1)
        ->and($dto->sections[0]->ayahFrom)->toBe(1)
        ->and($dto->sections[0]->ayahTo)->toBe(7)
        ->and($dto->sections[0]->sectionType)->toBe(ExamSectionType::Recitation)
        ->and($dto->sections[0]->maxScore)->toBe(100);
});

test('ExamSubmissionData tolerates optional fields and prebuilt section DTOs', function () {
    $section = new ExamSectionData(
        surahNumber: 2,
        ayahFrom: 1,
        ayahTo: 5,
        sectionType: ExamSectionType::Tajweed,
        maxScore: 50,
    );

    $dto = ExamSubmissionData::fromArray([
        'title' => 'امتحان',
        'exam_type' => 'monthly',
        'sections' => [$section],
    ]);

    expect($dto->mosqueId)->toBeNull()
        ->and($dto->circleId)->toBeNull()
        ->and($dto->scheduledAt)->toBeNull()
        ->and($dto->sections)->toBe([$section]);
});

test('ExamSubmissionData starts with no sections when the payload carries none', function () {
    $dto = ExamSubmissionData::fromArray([
        'title' => 'امتحان',
        'exam_type' => 'oral',
    ]);

    expect($dto->sections)->toBe([]);
});

test('an unknown section type is rejected before it reaches the database', function () {
    expect(fn () => ExamSectionData::fromArray([
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'section_type' => 'writing',
        'max_score' => 100,
    ]))->toThrow(ValueError::class);
});

test('ExamResultData maps the creatable statuses to the enum', function () {
    $dto = ExamResultData::fromArray([
        'exam_id' => '7',
        'exam_section_id' => '11',
        'student_id' => '3',
        'score' => '90',
        'grade' => 'ممتاز',
        'status' => 'submitted',
    ]);

    expect($dto->examId)->toBe(7)
        ->and($dto->examSectionId)->toBe(11)
        ->and($dto->studentId)->toBe(3)
        ->and($dto->score)->toBe(90)
        ->and($dto->grade)->toBe('ممتاز')
        ->and($dto->status)->toBe(ExamResultStatus::Submitted);
});

test('ExamResultData keeps the grade optional and accepts the enum directly', function () {
    $dto = ExamResultData::fromArray([
        'exam_id' => 7,
        'exam_section_id' => 11,
        'student_id' => 3,
        'score' => 0,
        'status' => ExamResultStatus::Draft,
    ]);

    expect($dto->grade)->toBeNull()
        ->and($dto->score)->toBe(0)
        ->and($dto->status)->toBe(ExamResultStatus::Draft);
});

test('ExamResultData rejects an unknown status', function () {
    expect(fn () => ExamResultData::fromArray([
        'exam_id' => 7,
        'exam_section_id' => 11,
        'student_id' => 3,
        'score' => 90,
        'status' => 'published',
    ]))->toThrow(ValueError::class);
});

test('ExamCorrectionData requires a reason and keeps the grade optional', function () {
    $dto = ExamCorrectionData::fromArray([
        'score' => '85',
        'reason' => 'خطأ في تسجيل الدرجة الأصلية',
    ]);

    expect($dto->score)->toBe(85)
        ->and($dto->grade)->toBeNull()
        ->and($dto->reason)->toBe('خطأ في تسجيل الدرجة الأصلية');
});

test('ExamUpdateData only exposes the attributes the client actually sent', function () {
    $titleOnly = ExamUpdateData::fromArray(['title' => 'الاسم الجديد']);

    expect($titleOnly->attributes())->toBe(['title' => 'الاسم الجديد'])
        ->and($titleOnly->scheduledAtProvided)->toBeFalse();

    $cleared = ExamUpdateData::fromArray(['scheduled_at' => null]);

    expect($cleared->scheduledAtProvided)->toBeTrue()
        ->and($cleared->attributes())->toBe(['scheduled_at' => null]);

    $statusOnly = ExamUpdateData::fromArray(['status' => 'completed']);

    expect($statusOnly->status)->toBe(ExamStatus::Completed)
        ->and($statusOnly->attributes())->toBe(['status' => ExamStatus::Completed]);

    $scheduled = ExamUpdateData::fromArray([
        'title' => 'عنوان',
        'scheduled_at' => '2026-02-01 08:00:00',
    ]);

    expect($scheduled->attributes())->toBe([
        'title' => 'عنوان',
        'scheduled_at' => '2026-02-01 08:00:00',
    ]);
});
