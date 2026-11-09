<?php

declare(strict_types=1);

use App\DTOs\CertificateData;

test('CertificateData maps the validated payload and keeps exam_id optional', function () {
    $withExam = CertificateData::fromArray([
        'student_id' => '15',
        'exam_id' => '4',
        'certificate_type' => 'إتمام حفظ',
    ]);

    $withoutExam = CertificateData::fromArray([
        'student_id' => 16,
        'certificate_type' => 'إتمام جزء',
    ]);

    expect($withExam->studentId)->toBe(15)
        ->and($withExam->examId)->toBe(4)
        ->and($withExam->certificateType)->toBe('إتمام حفظ')
        ->and($withoutExam->examId)->toBeNull();
});

test('CertificateData is readonly and cannot be mutated after construction', function () {
    $dto = CertificateData::fromArray([
        'student_id' => 1,
        'certificate_type' => 'إتمام حفظ',
    ]);

    expect(fn () => $dto->certificateType = 'تغيير')
        ->toThrow(Error::class);
});
