<?php

declare(strict_types=1);

use App\DTOs\AttendanceData;
use App\DTOs\BulkAttendanceData;
use App\Enums\AttendanceStatus;

test('attendance data maps validated input into a typed dto', function () {
    $data = AttendanceData::fromArray([
        'student_id' => '7',
        'status' => 'present',
        'date' => '2026-11-09',
        'notes' => '  حاضر  ',
    ]);

    expect($data->studentId)->toBe(7)
        ->and($data->status)->toBe(AttendanceStatus::Present)
        ->and($data->date)->toBe('2026-11-09')
        ->and($data->notes)->toBe('حاضر');
});

test('attendance data normalises blank optional values to null', function () {
    $data = AttendanceData::fromArray([
        'student_id' => 1,
        'status' => AttendanceStatus::Absent,
        'date' => '',
        'notes' => '   ',
    ]);

    expect($data->date)->toBeNull()
        ->and($data->notes)->toBeNull();
});

test('attendance data rejects an unknown status', function () {
    AttendanceData::fromArray([
        'student_id' => 1,
        'status' => 'not-a-status',
    ]);
})->throws(ValueError::class);

test('attendance data never carries tenant or actor columns', function () {
    $data = AttendanceData::fromArray([
        'student_id' => 1,
        'status' => 'late',
        'organization_id' => 999,
        'recorded_by' => 555,
        'circle_id' => 3,
    ]);

    // organization_id/recorded_by/circle_id تُشتق من الطالب والفاعل داخل الـ Action.
    expect(property_exists($data, 'organization_id'))->toBeFalse()
        ->and(property_exists($data, 'recorded_by'))->toBeFalse()
        ->and(property_exists($data, 'circle_id'))->toBeFalse();
});

test('bulk attendance data shares one date and circle across records', function () {
    $data = BulkAttendanceData::fromArray([
        'circle_id' => '4',
        'date' => '2026-11-09',
        'records' => [
            ['student_id' => '1', 'status' => 'present', 'notes' => ''],
            ['student_id' => 2, 'status' => 'absent'],
        ],
    ]);

    expect($data->circleId)->toBe(4)
        ->and($data->date)->toBe('2026-11-09')
        ->and($data->records)->toHaveCount(2)
        ->and($data->records[0]->studentId)->toBe(1)
        ->and($data->records[0]->status)->toBe(AttendanceStatus::Present)
        ->and($data->records[0]->notes)->toBeNull()
        ->and($data->records[0]->date)->toBe('2026-11-09')
        ->and($data->records[1]->studentId)->toBe(2)
        ->and($data->records[1]->status)->toBe(AttendanceStatus::Absent);
});

test('late counts as attended while absence and excuse do not', function () {
    expect(AttendanceStatus::Present->countsAsAttended())->toBeTrue()
        ->and(AttendanceStatus::Late->countsAsAttended())->toBeTrue()
        ->and(AttendanceStatus::Absent->countsAsAttended())->toBeFalse()
        ->and(AttendanceStatus::Excused->countsAsAttended())->toBeFalse();
});
