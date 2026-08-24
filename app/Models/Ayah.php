<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * جدول عالمي (Reference Data) — لا organization_id.
 */
class Ayah extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'surah_id',
        'number_in_surah',
        'number_global',
        'juz_number',
        'hizb_number',
        'page_number',
    ];

    protected function casts(): array
    {
        return [
            'number_in_surah' => 'integer',
            'number_global' => 'integer',
            'juz_number' => 'integer',
            'hizb_number' => 'integer',
            'page_number' => 'integer',
        ];
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
