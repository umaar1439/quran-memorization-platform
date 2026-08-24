<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\TeacherAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    /** @use HasFactory<TeacherAttendanceFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'teacher_id',
        'mosque_id',
        'date',
        'first_activity_at',
        'last_activity_at',
        'actions_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'first_activity_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'actions_count' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
