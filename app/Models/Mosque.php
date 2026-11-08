<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GenericStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MosqueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mosque extends Model
{
    /** @use HasFactory<MosqueFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'address',
        'city',
        'phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => GenericStatus::class,
        ];
    }

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }
}
