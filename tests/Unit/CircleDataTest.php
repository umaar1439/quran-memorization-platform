<?php

declare(strict_types=1);

use App\DTOs\CircleData;
use App\Enums\GenericStatus;

test('circle data maps validated input into a typed dto', function () {
    $data = CircleData::fromArray([
        'mosque_id' => '7',
        'teacher_id' => '12',
        'name' => '  حلقة الفجر  ',
        'level' => 'متقدم',
        'capacity' => '25',
        'status' => 'active',
    ]);

    expect($data->mosqueId)->toBe(7)
        ->and($data->teacherId)->toBe(12)
        ->and($data->name)->toBe('حلقة الفجر')
        ->and($data->level)->toBe('متقدم')
        ->and($data->capacity)->toBe(25)
        ->and($data->status)->toBe(GenericStatus::Active);
});

test('circle data normalises empty optional inputs to null instead of zero', function () {
    $data = CircleData::fromArray([
        'mosque_id' => 3,
        'teacher_id' => '',
        'name' => 'حلقة العصر',
        'level' => '   ',
        'capacity' => '',
        'status' => 'inactive',
    ]);

    expect($data->teacherId)->toBeNull()
        ->and($data->level)->toBeNull()
        ->and($data->capacity)->toBeNull()
        ->and($data->status)->toBe(GenericStatus::Inactive);
});

test('circle data never carries the organization id', function () {
    $data = CircleData::fromArray([
        'mosque_id' => 1,
        'name' => 'حلقة',
        'status' => 'active',
        'organization_id' => 42,
    ]);

    // الحلقة ترث منظمة الفاعل في الـ Action، ولا تقبلها من الحمولة.
    expect(property_exists($data, 'organization_id'))->toBeFalse();
});
