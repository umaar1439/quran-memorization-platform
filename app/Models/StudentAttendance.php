<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\StudentAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل حضور طالب في يوم محدد لحلقة محددة.
 *
 * العزل عبر Global Scope (BelongsToOrganization)، والقيد الفريد
 * (organization_id, student_id, date) يفرضه مخطط قاعدة البيانات لا الكود.
 */
class StudentAttendance extends Model
{
    /** @use HasFactory<StudentAttendanceFactory> */
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'student_id',
        'circle_id',
        'mosque_id',
        'date',
        'status',
        'notes',
        'recorded_by',
    ];

    /**
     * `date` بلا Cast مقصود: عمود DATE في MySQL/PostgreSQL يُخزَّن بلا وقت،
     * أما Eloquent فيكتب أي Cast تاريخي بصيغة Y-m-d H:i:s، وهو ما يُنتج
     * قيمتين نصيتين مختلفتين لليوم نفسه في SQLite (بيئة الاختبار). وبما أن
     * القيد الفريد (organization_id, student_id, date) هو أساس دلالات
     * التصحيح وupsert، يجب أن تكون القيمة المخزَّنة قانونية دائماً: نص
     * Y-m-d واحد يكتبه كل من RecordAttendanceAction وBulkRecordAttendanceAction.
     */
    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    /**
     * من سجّل الحضور. الاسم `recorder` لا `recordedBy` لأن Eloquent يُسلسِل
     * أسماء العلاقات بصيغة snake_case، فيتحول recordedBy إلى recorded_by
     * ويطمس عمود recorded_by الرقمي في استجابة JSON.
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
