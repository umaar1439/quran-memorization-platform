<?php

declare(strict_types=1);

use App\DTOs\StudentGoalData;
use App\DTOs\StudentGoalUpdateData;
use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;

test('StudentGoalData maps the validated create payload to typed values', function () {
    $dto = StudentGoalData::fromArray([
        'student_id' => '42',
        'period_type' => 'weekly',
        'target_type' => 'memorization',
        'surah_id' => '2',
        'target_amount' => '5',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-07',
    ]);

    expect($dto->studentId)->toBe(42)
        ->and($dto->periodType)->toBe(PeriodType::Weekly)
        ->and($dto->targetType)->toBe(GoalTargetType::Memorization)
        ->and($dto->surahId)->toBe(2)
        ->and($dto->juzNumber)->toBeNull()
        ->and($dto->targetAmount)->toBe(5)
        ->and($dto->periodStart)->toBe('2026-01-01')
        ->and($dto->periodEnd)->toBe('2026-01-07');
});

test('StudentGoalData never carries server-owned attributes', function () {
    $dto = StudentGoalData::fromArray([
        'student_id' => 1,
        'period_type' => 'daily',
        'target_type' => 'revision',
        'juz_number' => '3',
        'target_amount' => 1,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-01',
        // حقول يحاول العميل فرضها: لا تصل إلى toAttributes() إطلاقاً.
        'organization_id' => 999,
        'achieved_amount' => 99,
        'status' => 'completed',
    ]);

    $attributes = $dto->toAttributes();

    expect($attributes)->not->toHaveKey('organization_id')
        ->and($attributes)->not->toHaveKey('achieved_amount')
        ->and($attributes)->not->toHaveKey('status')
        ->and($attributes['juz_number'])->toBe(3)
        ->and($attributes['surah_id'])->toBeNull();
});

test('StudentGoalUpdateData keeps only the fields the client actually sent', function () {
    $dto = StudentGoalUpdateData::fromArray(['achieved_amount' => '0']);

    expect($dto->attributes())->toBe(['achieved_amount' => 0]);
});

test('StudentGoalUpdateData distinguishes an explicit null from an absent scope field', function () {
    $cleared = StudentGoalUpdateData::fromArray(['surah_id' => null, 'juz_number' => 7]);
    $absent = StudentGoalUpdateData::fromArray(['target_amount' => 3]);

    expect($cleared->attributes())->toBe(['surah_id' => null, 'juz_number' => 7])
        ->and($absent->attributes())->toBe(['target_amount' => 3]);
});

test('StudentGoalUpdateData casts enums and accepts the completed status', function () {
    $dto = StudentGoalUpdateData::fromArray([
        'period_type' => 'monthly',
        'target_type' => 'revision',
        'status' => 'completed',
    ]);

    expect($dto->attributes())->toBe([
        'period_type' => PeriodType::Monthly,
        'target_type' => GoalTargetType::Revision,
        'status' => GoalStatus::Completed,
    ]);
});
