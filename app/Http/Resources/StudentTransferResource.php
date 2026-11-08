<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\StudentTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentTransfer
 */
class StudentTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'from_mosque_id' => $this->from_mosque_id,
            'to_mosque_id' => $this->to_mosque_id,
            'from_circle_id' => $this->from_circle_id,
            'to_circle_id' => $this->to_circle_id,
            'transferred_by_user_id' => $this->transferred_by_user_id,
            'reason' => $this->reason,
            'transferred_at' => $this->transferred_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
