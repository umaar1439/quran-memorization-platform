<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Surah;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Surah
 */
class SurahResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name_arabic' => $this->name_arabic,
            'name_english' => $this->name_english,
            'revelation_type' => $this->revelation_type,
            'ayah_count' => $this->ayah_count,
        ];
    }
}
