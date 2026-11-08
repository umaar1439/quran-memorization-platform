<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StudentGoal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentGoal
 */
class StudentGoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'period_type' => $this->period_type,
            'target_type' => $this->target_type,
            'surah_id' => $this->surah_id,
            'juz_number' => $this->juz_number,
            'target_amount' => $this->target_amount,
            'achieved_amount' => $this->achieved_amount,
            'completion_percentage' => $this->completion_percentage,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status?->value ?? $this->status,
            'surah' => new SurahResource($this->whenLoaded('surah')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
