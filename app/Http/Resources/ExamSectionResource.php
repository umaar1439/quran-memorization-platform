<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ExamSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExamSection
 */
class ExamSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'surah_number' => $this->surah_number,
            'ayah_from' => $this->ayah_from,
            'ayah_to' => $this->ayah_to,
            // القيمة النصية للـ enum (مطابقة لنمط ExamResource::status) حتى لا
            // يعتمد العقد الأمامي على تسلسل الأداة لِـ BackedEnum.
            'section_type' => $this->section_type?->value ?? $this->section_type,
            'max_score' => $this->max_score,
        ];
    }
}
