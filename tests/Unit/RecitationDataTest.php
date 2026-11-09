<?php

declare(strict_types=1);

use App\DTOs\RecitationData;
use App\Enums\RecitationRating;
use App\Enums\RecitationType;

test('RecitationData maps a validated payload to a typed readonly object', function () {
    $dto = RecitationData::fromArray([
        'student_id' => '12',
        'type' => 'near_revision',
        'surah_number' => '2',
        'ayah_from' => '1',
        'ayah_to' => '5',
        'rating' => 'very_good',
        'notes' => 'مراجعة قريبة',
        'performed_at' => '2024-05-01 10:00:00',
        'idempotency_key' => '3f7c2c1e-9c8a-4b1a-9f1e-0d6b0f9d1a2b',
    ]);

    expect($dto->studentId)->toBe(12)
        ->and($dto->type)->toBe(RecitationType::NearRevision)
        ->and($dto->surahNumber)->toBe(2)
        ->and($dto->ayahFrom)->toBe(1)
        ->and($dto->ayahTo)->toBe(5)
        ->and($dto->rating)->toBe(RecitationRating::VeryGood)
        ->and($dto->notes)->toBe('مراجعة قريبة')
        ->and($dto->performedAt)->toBe('2024-05-01 10:00:00')
        ->and($dto->idempotencyKey)->toBe('3f7c2c1e-9c8a-4b1a-9f1e-0d6b0f9d1a2b')
        ->and($dto->errors)->toBe([]);
});

test('RecitationData accepts enum instances and optional fields from internal callers', function () {
    $dto = RecitationData::fromArray([
        'student_id' => 3,
        'type' => RecitationType::New,
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => 'local-key',
    ]);

    expect($dto->type)->toBe(RecitationType::New)
        ->and($dto->rating)->toBeNull()
        ->and($dto->notes)->toBeNull()
        ->and($dto->performedAt)->toBeNull();
});

test('RecitationData keeps the optional error rows reported by the caller', function () {
    $errors = [
        ['error_type' => 'tajweed', 'count' => 2, 'notes' => 'مد'],
        ['error_type' => 'hifz'],
    ];

    $dto = RecitationData::fromArray([
        'student_id' => 3,
        'type' => 'new',
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => 'local-key',
        'errors' => $errors,
    ]);

    expect($dto->errors)->toBe($errors);
});

test('RecitationData rejects an unknown recitation type before it reaches the database', function () {
    expect(fn () => RecitationData::fromArray([
        'student_id' => 3,
        'type' => 'memorized_everything',
        'surah_number' => 1,
        'ayah_from' => 1,
        'ayah_to' => 7,
        'idempotency_key' => 'local-key',
    ]))->toThrow(ValueError::class);
});
