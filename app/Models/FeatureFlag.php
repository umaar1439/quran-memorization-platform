<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * جدول مستقل تماماً عن Settings — يتحكم في تشغيل/تعطيل وظيفة كاملة
 * من النظام (Boolean فقط). organization_id nullable = علم افتراضي عالمي
 * قابل للتجاوز على مستوى منظمة (انظر §7).
 */
class FeatureFlag extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'key',
        'enabled',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
