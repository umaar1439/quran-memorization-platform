<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'student_id',
        'from_mosque_id',
        'to_mosque_id',
        'from_circle_id',
        'to_circle_id',
        'transferred_by_user_id',
        'reason',
        'metadata',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'transferred_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function fromMosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class, 'from_mosque_id');
    }

    public function toMosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class, 'to_mosque_id');
    }

    public function fromCircle(): BelongsTo
    {
        return $this->belongsTo(Circle::class, 'from_circle_id');
    }

    public function toCircle(): BelongsTo
    {
        return $this->belongsTo(Circle::class, 'to_circle_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by_user_id');
    }
}
