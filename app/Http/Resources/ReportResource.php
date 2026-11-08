<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * `file_path` مستبعَد عمداً — التحميل يتم عبر مسار مُصرَّح به فقط.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requested_by' => $this->requested_by,
            'report_type' => $this->report_type,
            'parameters' => $this->parameters,
            'status' => $this->status?->value ?? $this->status,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
