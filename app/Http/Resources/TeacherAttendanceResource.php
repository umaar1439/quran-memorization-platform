<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeacherAttendance
 */
class TeacherAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'mosque_id' => $this->mosque_id,
            'date' => $this->date?->toDateString(),
            'first_activity_at' => $this->first_activity_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'actions_count' => $this->actions_count,
        ];
    }
}
