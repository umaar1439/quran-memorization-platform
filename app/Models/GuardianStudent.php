<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * جدول Pivot يربط ولي الأمر بالطالب مع صلاحيات دقيقة (JSONB) لكل ابن.
 * لا يستخدم BelongsToOrganization لأنه Pivot Model صريح لا Model عادي،
 * ويُصفّى ضمنياً عبر علاقاته (Guardian/StudentProfile) التي تخضع للـ Global Scope.
 */
class GuardianStudent extends Pivot
{
    protected $table = 'guardian_student';

    protected $fillable = [
        'guardian_id',
        'student_id',
        'organization_id',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student()
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }
}
