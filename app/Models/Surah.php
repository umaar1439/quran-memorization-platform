<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RevelationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * جدول عالمي (Reference Data) — لا organization_id، مشترك بين كل المستأجرين.
 * قراءة فقط من التطبيق بعد الـ Seed الأولي.
 */
class Surah extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'number',
        'name_arabic',
        'name_english',
        'revelation_type',
        'ayah_count',
        'page_start',
    ];

    protected function casts(): array
    {
        return [
            'revelation_type' => RevelationType::class,
            'number' => 'integer',
            'ayah_count' => 'integer',
            'page_start' => 'integer',
        ];
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class);
    }
}
