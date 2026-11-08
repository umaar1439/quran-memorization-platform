<?php

declare(strict_types=1);

use App\DTOs\StudentData;
use App\DTOs\TeacherData;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;

test('TeacherData maps a validated array to a typed readonly object', function () {
    $dto = TeacherData::fromArray([
        'user_id' => '7',
        'name' => 'معلم',
        'email' => 'teacher@example.com',
        'password' => 'secret-password',
        'phone' => '0555555555',
        'specialization' => 'تجويد',
        'ijazah_details' => 'إجازة',
        'hired_at' => '2024-01-01',
        'status' => 'active',
        'circles' => ['3', 5],
    ]);

    expect($dto->userId)->toBe(7)
        ->and($dto->name)->toBe('معلم')
        ->and($dto->email)->toBe('teacher@example.com')
        ->and($dto->password)->toBe('secret-password')
        ->and($dto->phone)->toBe('0555555555')
        ->and($dto->specialization)->toBe('تجويد')
        ->and($dto->ijazahDetails)->toBe('إجازة')
        ->and($dto->hiredAt)->toBe('2024-01-01')
        ->and($dto->status)->toBe(UserStatus::Active)
        ->and($dto->circleIds)->toBe([3, 5]);
});

test('TeacherData handles the optional fields of an update payload', function () {
    $dto = TeacherData::fromArray([
        'name' => 'معلم',
        'email' => 'teacher@example.com',
        'status' => 'suspended',
        'circles' => [1],
    ]);

    expect($dto->userId)->toBeNull()
        ->and($dto->password)->toBeNull()
        ->and($dto->phone)->toBeNull()
        ->and($dto->status)->toBe(UserStatus::Suspended)
        ->and($dto->circleIds)->toBe([1]);
});

test('StudentData casts identifiers and keeps an explicit status enum', function () {
    $dto = StudentData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => 'secret-password',
        'circle_id' => '12',
        'enrollment_number' => 'STU-1',
        'current_juz' => '4',
        'status' => 'active',
    ]);

    expect($dto->circleId)->toBe(12)
        ->and($dto->currentJuz)->toBe(4)
        ->and($dto->birthDate)->toBeNull()
        ->and($dto->status)->toBe(StudentStatus::Active);
});

test('StudentData is readonly and cannot be mutated after construction', function () {
    $dto = StudentData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => 'secret-password',
        'circle_id' => 1,
        'enrollment_number' => 'STU-2',
        'status' => 'active',
    ]);

    expect(fn () => $dto->name = 'تغيير')
        ->toThrow(Error::class);
});
