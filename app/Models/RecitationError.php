<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecitationErrorType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecitationError extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'recitation_id',
        'organization_id',
        'error_type',
        'count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'error_type' => RecitationErrorType::class,
            'count' => 'integer',
        ];
    }

    public function recitation(): BelongsTo
    {
        return $this->belongsTo(Recitation::class);
    }
}
