<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * بيانات طلب تقرير جديد بعد المصادقة، تُبنى من
 * StoreReportRequest::toData() وتُمرَّر إلى GenerateReportAction.
 *
 * `organization_id` و`requested_by` و`status` غائبة عمداً: تُشتق من المستخدم
 * الحالي/الخادم ولا يُسمح للعميل بفرضها.
 */
final readonly class ReportData
{
    /**
     * @param  array<string, mixed>|null  $parameters
     */
    public function __construct(
        public string $reportType,
        public ?array $parameters,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $parameters = $data['parameters'] ?? null;

        return new self(
            reportType: (string) $data['report_type'],
            parameters: is_array($parameters) ? $parameters : null,
        );
    }
}
