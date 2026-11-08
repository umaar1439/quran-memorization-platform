<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGoal extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'student_id',
        'period_type',
        'target_type',
        'surah_id',
        'juz_number',
        'target_amount',
        'achieved_amount',
        'completion_percentage',
        'period_start',
        'period_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'period_type' => PeriodType::class,
            'target_type' => GoalTargetType::class,
            'status' => GoalStatus::class,
            'juz_number' => 'integer',
            'target_amount' => 'integer',
            'achieved_amount' => 'integer',
            'completion_percentage' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
