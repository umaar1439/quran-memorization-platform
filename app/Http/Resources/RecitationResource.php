<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Recitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recitation
 */
class RecitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'mosque_id' => $this->mosque_id,
            'circle_id' => $this->circle_id,
            'type' => $this->type,
            'surah_number' => $this->surah_number,
            'ayah_from' => $this->ayah_from,
            'ayah_to' => $this->ayah_to,
            'rating' => $this->rating,
            'notes' => $this->notes,
            'performed_at' => $this->performed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'errors' => RecitationErrorResource::collection($this->whenLoaded('errors')),
            'student' => new StudentProfileResource($this->whenLoaded('student')),
        ];
    }
}
