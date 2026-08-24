<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'requested_by',
        'report_type',
        'parameters',
        'status',
        'file_path',
        'expires_at',
    ];

    /**
     * الملف يُخزَّن في Private Disk — لا يُصرَّح بالوصول المباشر عبر URL عام.
     */
    protected $hidden = [
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'status' => ReportStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
