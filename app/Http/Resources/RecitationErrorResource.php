<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\RecitationError;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecitationError
 */
class RecitationErrorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recitation_id' => $this->recitation_id,
            'error_type' => $this->error_type,
            'count' => $this->count,
            'notes' => $this->notes,
        ];
    }
}
