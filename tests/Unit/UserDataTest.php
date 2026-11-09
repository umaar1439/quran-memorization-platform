<?php

declare(strict_types=1);

use App\DTOs\UserData;
use App\Enums\UserStatus;

test('UserData maps a validated create payload to a typed readonly object', function () {
    $dto = UserData::fromArray([
        'name' => 'مشرف جديد',
        'email' => 'supervisor@example.com',
        'password' => 'secret-password',
        'phone' => '0551234567',
        'role' => 'supervisor',
        'status' => 'active',
    ]);

    expect($dto->name)->toBe('مشرف جديد')
        ->and($dto->email)->toBe('supervisor@example.com')
        ->and($dto->password)->toBe('secret-password')
        ->and($dto->phone)->toBe('0551234567')
        ->and($dto->role)->toBe('supervisor')
        ->and($dto->status)->toBe(UserStatus::Active)
        ->and($dto->hasPassword())->toBeTrue();
});

test('UserData treats a blank password and phone as "not sent"', function () {
    $dto = UserData::fromArray([
        'name' => 'معلم',
        'email' => 'teacher@example.com',
        'password' => '',
        'phone' => null,
        'role' => 'teacher',
        'status' => 'suspended',
    ]);

    expect($dto->password)->toBeNull()
        ->and($dto->phone)->toBeNull()
        ->and($dto->hasPassword())->toBeFalse()
        ->and($dto->status)->toBe(UserStatus::Suspended);
});

test('UserData exposes only the fillable attributes to the model', function () {
    $dto = UserData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => 'secret-password',
        'role' => 'student',
        'status' => 'active',
    ]);

    // organization_id/role/status/password لا تُعبَّأ جماعياً — تُعيَّن صريحاً.
    expect($dto->attributes())->toBe([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'phone' => null,
    ]);
});

test('UserData is readonly and cannot be mutated after construction', function () {
    $dto = UserData::fromArray([
        'name' => 'ولي أمر',
        'email' => 'parent@example.com',
        'password' => 'secret-password',
        'role' => 'parent',
        'status' => 'active',
    ]);

    expect(fn () => $dto->name = 'تغيير')
        ->toThrow(Error::class);
});
