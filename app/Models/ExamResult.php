<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamResultStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الثبات بعد الاعتماد (Immutability After Approval): بعد status=approved،
 * لا يجوز تعديل score/grade مباشرة — يُفرض عبر Policy/Application Logic،
 * وأي تصحيح يجب أن يمر عبر Correction Workflow مخصص مع Audit Log كامل.
 */
class ExamResult extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'exam_id',
        'exam_section_id',
        'student_id',
        'organization_id',
        'score',
        'grade',
        'examiner_id',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ExamResultStatus::class,
            'score' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function examSection(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === ExamResultStatus::Approved;
    }
}
