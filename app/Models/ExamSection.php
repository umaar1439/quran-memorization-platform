<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamSectionType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSection extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'exam_id',
        'organization_id',
        'surah_number',
        'ayah_from',
        'ayah_to',
        'section_type',
        'max_score',
    ];

    protected function casts(): array
    {
        return [
            'section_type' => ExamSectionType::class,
            'surah_number' => 'integer',
            'ayah_from' => 'integer',
            'ayah_to' => 'integer',
            'max_score' => 'integer',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
