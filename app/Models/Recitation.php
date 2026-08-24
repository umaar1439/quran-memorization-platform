<?php

namespace App\Models;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\RecitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recitation extends Model
{
    /** @use HasFactory<RecitationFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'student_id',
        'teacher_id',
        'mosque_id',
        'circle_id',
        'type',
        'surah_number',
        'ayah_from',
        'ayah_to',
        'rating',
        'notes',
        'performed_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'type' => RecitationType::class,
            'rating' => RecitationRating::class,
            'surah_number' => 'integer',
            'ayah_from' => 'integer',
            'ayah_to' => 'integer',
            'performed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(RecitationError::class);
    }
}
