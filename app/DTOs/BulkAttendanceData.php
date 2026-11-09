<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * ورقة الحضور اليومية: حلقة واحدة + تاريخ واحد + سجلات طلابها، تُبنى من
 * BulkAttendanceRequest::toData() وتُمرَّر إلى BulkRecordAttendanceAction.
 *
 * الحلقة والتاريخ على مستوى الورقة لا لكل سجل: الورقة تمثّل حصة/يوماً واحداً،
 * ووجود حلقتين أو تاريخين في طلب واحد يعني إما خطأ إدخال أو محاولة تحايل على
 * القيد الفريد (organization_id, student_id, date). وربط الورقة بحلقة واحدة
 * يجعل هدف سجل التدقيق المُجمَّع واضحاً بدل سجل بلا كيان مرجعي.
 */
final readonly class BulkAttendanceData
{
    /**
     * @param  list<AttendanceData>  $records
     */
    public function __construct(
        public int $circleId,
        public string $date,
        public array $records,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $date = trim((string) $data['date']);

        // إعادة استخدام AttendanceData::fromArray تمنع تكرار منطق التطبيع
        // (تحويل الحالة إلى Enum وتفريغ الملاحظات الفارغة) في مكانين.
        $records = array_map(
            static fn (array $record): AttendanceData => AttendanceData::fromArray([
                ...$record,
                'date' => $date,
            ]),
            array_values((array) $data['records']),
        );

        return new self(
            circleId: (int) $data['circle_id'],
            date: $date,
            records: $records,
        );
    }
}
