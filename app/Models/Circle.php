<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GenericStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\CircleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Circle extends Model
{
    /** @use HasFactory<CircleFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'mosque_id',
        'teacher_id',
        'name',
        'level',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => GenericStatus::class,
            'capacity' => 'integer',
        ];
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }
}
