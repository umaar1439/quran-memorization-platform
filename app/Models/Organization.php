<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * جذر تعدد المستأجرين (Tenant Root). لا يستخدم BelongsToOrganization
 * لأنه هو المنظمة نفسها وليس كياناً تابعاً لها.
 */
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function mosques(): HasMany
    {
        return $this->hasMany(Mosque::class);
    }

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
