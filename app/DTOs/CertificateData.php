<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * بيانات إصدار شهادة بعد المصادقة، تُبنى من
 * StoreCertificateRequest::toData() وتُمرَّر إلى GenerateCertificateAction.
 *
 * `organization_id` و`verification_code` و`status` غائبة عمداً: تُشتق من
 * ملف الطالب/الخادم ولا يُسمح للعميل بفرضها.
 */
final readonly class CertificateData
{
    public function __construct(
        public int $studentId,
        public ?int $examId,
        public string $certificateType,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: (int) $data['student_id'],
            examId: isset($data['exam_id']) ? (int) $data['exam_id'] : null,
            certificateType: (string) $data['certificate_type'],
        );
    }
}
