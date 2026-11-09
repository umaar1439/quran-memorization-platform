<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\AttendanceStatus;

/**
 * بيانات مُصادَق عليها لتسجيل/تصحيح حضور طالب في يوم واحد، تُبنى من
 * StoreAttendanceRequest::toData() وتُمرَّر إلى RecordAttendanceAction بديلاً
 * عن المصفوفة الحرّة (Loose Array).
 *
 * `organization_id` و`circle_id` و`mosque_id` و`recorded_by` غير موجودة عمداً:
 * تُشتق كلها من الطالب والفاعل داخل الـ Action، فلا يمكن للعميل تزوير انتساب
 * عابر للمنظمات أو نسب السجل إلى مستخدم آخر عبر الحمولة.
 *
 * `date` اختيارية (null = اليوم) لأن الواجهة تسجّل حضور اليوم افتراضياً.
 */
final readonly class AttendanceData
{
    public function __construct(
        public int $studentId,
        public AttendanceStatus $status,
        public ?string $date,
        public ?string $notes,
    ) {}

    /**
     * يقبل القيم كنصوص (من validated()) أو كـ Enum (استدعاء مباشر من الاختبارات).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: (int) $data['student_id'],
            status: $data['status'] instanceof AttendanceStatus
                ? $data['status']
                : AttendanceStatus::from((string) $data['status']),
            date: self::blankToNull($data['date'] ?? null),
            notes: self::blankToNull($data['notes'] ?? null),
        );
    }

    /**
     * القيم النصية الفارغة تُطبَّع إلى null حتى لا تصل `''` إلى قاعدة البيانات.
     */
    private static function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
