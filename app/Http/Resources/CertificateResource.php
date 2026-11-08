<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Certificate
 */
class CertificateResource extends JsonResource
{
    /**
     * `file_path` مستبعَد عمداً: مسار تخزين داخلي لا يُكشف للعميل (§4).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'exam_id' => $this->exam_id,
            'certificate_type' => $this->certificate_type,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'issued_by' => $this->issued_by,
            'verification_code' => $this->verification_code,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
