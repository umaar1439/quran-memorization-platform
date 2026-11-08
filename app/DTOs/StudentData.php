<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StudentStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث طالب (StudentProfile + حساب User).
 * `mosque_id` يُشتق من الحلقة داخل الـ Action ولا يُقبل من العميل إطلاقاً.
 */
final readonly class StudentData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public int $circleId,
        public string $enrollmentNumber,
        public ?string $birthDate,
        public ?int $currentJuz,
        public StudentStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: isset($data['password']) ? (string) $data['password'] : null,
            circleId: (int) $data['circle_id'],
            enrollmentNumber: (string) $data['enrollment_number'],
            birthDate: isset($data['birth_date']) ? (string) $data['birth_date'] : null,
            currentJuz: isset($data['current_juz']) ? (int) $data['current_juz'] : null,
            status: StudentStatus::from((string) $data['status']),
        );
    }
}
