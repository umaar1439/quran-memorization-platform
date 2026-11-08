<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exam
 */
class ExamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mosque_id' => $this->mosque_id,
            'circle_id' => $this->circle_id,
            'title' => $this->title,
            'exam_type' => $this->exam_type,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status?->value ?? $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'sections' => ExamSectionResource::collection($this->whenLoaded('sections')),
            'results' => ExamResultResource::collection($this->whenLoaded('results')),
        ];
    }
}
