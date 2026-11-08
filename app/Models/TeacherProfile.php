<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TeacherProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherProfile extends Model
{
    /** @use HasFactory<TeacherProfileFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'specialization',
        'ijazah_details',
        'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
