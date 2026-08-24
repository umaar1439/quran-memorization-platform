<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'student_id',
        'exam_id',
        'certificate_type',
        'issued_at',
        'issued_by',
        'verification_code',
        'file_path',
        'status',
    ];

    /**
     * الملف يُخزَّن في Private Disk — لا يُصرَّح بالوصول المباشر عبر URL عام؛
     * نُخفي file_path من التسلسل الافتراضي للحماية من التسريب العرضي.
     */
    protected $hidden = [
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => CertificateStatus::class,
            'issued_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
