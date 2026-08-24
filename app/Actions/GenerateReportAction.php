<?php

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * ينشئ سجل Report بحالة queued ويُطلق Job للتوليد الفعلي (§1 Reports —
 * Queue Job غير متزامن). يتحقق من صلاحية reports.generate قبل الإنشاء
 * (فحص أول عند الطلب؛ الفحص الثاني يحدث في الـ Job نفسه عند التنفيذ —
 * §4 تهديد #2 Broken Access Control، دفاع طبقتين).
 */
class GenerateReportAction
{
    /**
     * @param  array<string, mixed>  $data  report_type, parameters (اختياري)
     */
    public function execute(array $data, User $actor): Report
    {
        if (! $actor->can('reports.generate')) {
            throw ValidationException::withMessages([
                'actor' => 'لا تملك صلاحية توليد التقارير.',
            ]);
        }

        if ($actor->organization_id === null) {
            throw ValidationException::withMessages([
                'actor' => 'يجب أن ينتمي المستخدم لمنظمة لتوليد تقرير.',
            ]);
        }

        $report = Report::create([
            'organization_id' => $actor->organization_id,
            'requested_by' => $actor->id,
            'report_type' => $data['report_type'],
            'parameters' => $data['parameters'] ?? null,
            'status' => ReportStatus::Queued,
        ]);

        GenerateReportJob::dispatch($report->id, $actor->id);

        return $report;
    }
}
