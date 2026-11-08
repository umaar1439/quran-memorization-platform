<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExamResult
 */
class ExamResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'exam_section_id' => $this->exam_section_id,
            'student_id' => $this->student_id,
            'score' => $this->score,
            'grade' => $this->grade,
            'examiner_id' => $this->examiner_id,
            'status' => $this->status?->value ?? $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'student' => new StudentProfileResource($this->whenLoaded('student')),
        ];
    }
}
