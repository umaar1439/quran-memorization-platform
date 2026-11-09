<?php

declare(strict_types=1);

use App\DTOs\StudentData;
use App\Enums\StudentStatus;

test('student data maps validated input into a typed dto', function () {
    $data = StudentData::fromArray([
        'name' => '  أحمد علي  ',
        'email' => '  ahmed@example.com ',
        'password' => 'secret-password',
        'circle_id' => '7',
        'enrollment_number' => '  STU-1001  ',
        'birth_date' => '2010-05-01',
        'current_juz' => '3',
        'status' => 'active',
    ]);

    expect($data->name)->toBe('أحمد علي')
        ->and($data->email)->toBe('ahmed@example.com')
        ->and($data->password)->toBe('secret-password')
        ->and($data->circleId)->toBe(7)
        ->and($data->enrollmentNumber)->toBe('STU-1001')
        ->and($data->birthDate)->toBe('2010-05-01')
        ->and($data->currentJuz)->toBe(3)
        ->and($data->status)->toBe(StudentStatus::Active);
});

test('student data normalises blank optional values to null', function () {
    $data = StudentData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => '',
        'circle_id' => 1,
        'enrollment_number' => 'STU-1002',
        'birth_date' => '   ',
        'current_juz' => '',
        'status' => 'transferred',
    ]);

    // كلمة المرور الفارغة في التعديل تعني «بلا تغيير»، لا جزئية فارغة.
    expect($data->password)->toBeNull()
        ->and($data->birthDate)->toBeNull()
        ->and($data->currentJuz)->toBeNull()
        ->and($data->status)->toBe(StudentStatus::Transferred);
});

test('student data never carries tenant or derived mosque columns', function () {
    $data = StudentData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => 'secret-password',
        'circle_id' => 1,
        'enrollment_number' => 'STU-1003',
        'status' => 'active',
        'organization_id' => 999,
        'mosque_id' => 555,
    ]);

    // organization_id من الفاعل وmosque_id من الحلقة داخل الـ Action.
    expect(property_exists($data, 'organization_id'))->toBeFalse()
        ->and(property_exists($data, 'mosque_id'))->toBeFalse();
});

test('student data rejects an unknown status', function () {
    StudentData::fromArray([
        'name' => 'طالب',
        'email' => 'student@example.com',
        'password' => 'secret-password',
        'circle_id' => 1,
        'enrollment_number' => 'STU-1004',
        'status' => 'not-a-status',
    ]);
})->throws(ValueError::class);
