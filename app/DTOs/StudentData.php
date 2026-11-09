<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StudentStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث طالب (StudentProfile + حساب User).
 *
 * `organization_id` و`mosque_id` غير موجودين عمداً: الأول يُشتق من الفاعل
 * والثاني من الحلقة داخل الـ Action، فلا يمكن للعميل تزوير انتساب عابر
 * للمنظمات عبر الحمولة.
 *
 * `password` اختياري: مطلوب عند الإنشاء، واختياري عند التعديل (فارغ = لا
 * تغيير). القيم النصية الفارغة تُطبَّع إلى null هنا حتى لا تصل `''` إلى
 * قاعدة البيانات أو إلى منطق الـ Action.
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
            name: trim((string) $data['name']),
            email: trim((string) $data['email']),
            password: self::blankToNull($data['password'] ?? null),
            circleId: (int) $data['circle_id'],
            enrollmentNumber: trim((string) $data['enrollment_number']),
            birthDate: self::blankToNull($data['birth_date'] ?? null),
            currentJuz: isset($data['current_juz']) && $data['current_juz'] !== ''
                ? (int) $data['current_juz']
                : null,
            status: StudentStatus::from((string) $data['status']),
        );
    }

    private static function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
