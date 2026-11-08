<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentProfile
 */
class StudentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'mosque_id' => $this->mosque_id,
            'circle_id' => $this->circle_id,
            'enrollment_number' => $this->enrollment_number,
            'birth_date' => $this->birth_date?->toDateString(),
            'current_juz' => $this->current_juz,
            'status' => $this->status?->value ?? $this->status,
            'user' => new UserSummaryResource($this->whenLoaded('user')),
        ];
    }
}
