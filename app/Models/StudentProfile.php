<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StudentStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'mosque_id',
        'circle_id',
        'enrollment_number',
        'birth_date',
        'current_juz',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'current_juz' => 'integer',
            'status' => StudentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class, 'student_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(StudentGoal::class, 'student_id');
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class, 'student_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'student_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withPivot(['organization_id', 'permissions'])
            ->withTimestamps();
    }
}
