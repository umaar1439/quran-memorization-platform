<?php

declare(strict_types=1);

use App\DTOs\EnrollmentData;

test('enrollment data maps validated transfer input into a typed dto', function () {
    $data = EnrollmentData::fromArray([
        'student_id' => '12',
        'to_mosque_id' => '4',
        'to_circle_id' => '9',
        'reason' => '  انتقال داخل نفس المسجد  ',
        'metadata' => ['requested_by' => 'parent'],
    ]);

    expect($data->studentId)->toBe(12)
        ->and($data->toMosqueId)->toBe(4)
        ->and($data->toCircleId)->toBe(9)
        ->and($data->reason)->toBe('انتقال داخل نفس المسجد')
        ->and($data->metadata)->toBe(['requested_by' => 'parent']);
});

test('enrollment data normalises blank target circle, reason and metadata to null', function () {
    $data = EnrollmentData::fromArray([
        'student_id' => 12,
        'to_mosque_id' => 4,
        'to_circle_id' => '',
        'reason' => '   ',
        'metadata' => [],
    ]);

    // النقل بلا حلقة هدف يعني «خارج الحلقات» داخل المسجد نفسه.
    expect($data->toCircleId)->toBeNull()
        ->and($data->reason)->toBeNull()
        ->and($data->metadata)->toBeNull();
});

test('enrollment data never carries the organization id', function () {
    $data = EnrollmentData::fromArray([
        'student_id' => 1,
        'to_mosque_id' => 2,
        'organization_id' => 999,
    ]);

    expect(property_exists($data, 'organization_id'))->toBeFalse();
});
