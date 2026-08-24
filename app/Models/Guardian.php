<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'phone',
        'email',
        'relationship_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withPivot(['organization_id', 'permissions'])
            ->withTimestamps();
    }
}
