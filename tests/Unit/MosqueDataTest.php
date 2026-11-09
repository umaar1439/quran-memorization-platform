<?php

declare(strict_types=1);

use App\DTOs\MosqueData;
use App\Enums\GenericStatus;

test('mosque data maps validated input into a typed dto', function () {
    $data = MosqueData::fromArray([
        'name' => '  مسجد النور  ',
        'address' => 'حي السلام',
        'city' => 'الرياض',
        'phone' => '0551234567',
        'status' => 'active',
    ]);

    expect($data->name)->toBe('مسجد النور')
        ->and($data->address)->toBe('حي السلام')
        ->and($data->city)->toBe('الرياض')
        ->and($data->phone)->toBe('0551234567')
        ->and($data->status)->toBe(GenericStatus::Active);
});

test('mosque data normalises blank optional values to null', function () {
    $data = MosqueData::fromArray([
        'name' => 'مسجد الفرقان',
        'address' => '   ',
        'city' => '',
        'phone' => null,
        'status' => 'inactive',
    ]);

    expect($data->address)->toBeNull()
        ->and($data->city)->toBeNull()
        ->and($data->phone)->toBeNull()
        ->and($data->status)->toBe(GenericStatus::Inactive);
});

test('mosque data never carries the organization id', function () {
    $data = MosqueData::fromArray([
        'name' => 'مسجد',
        'status' => 'active',
        'organization_id' => 999,
    ]);

    // organization_id يُشتق من الفاعل في الـ Action ولا يُقبل من العميل.
    expect(property_exists($data, 'organization_id'))->toBeFalse();
});
