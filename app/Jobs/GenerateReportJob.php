<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\Recitation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * يُولّد تقريراً بسيطاً (JSON مبدئياً) ويُحدّث حالة Report. يتحقق مجدداً
 * من الصلاحية وfeature flag عند التنفيذ (ليس فقط عند الإنشاء) — §4
 * تهديد #2 Broken Access Control، دفاع طبقتين (Defense-in-Depth).
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $reportId,
        public readonly int $requestedByUserId,
    ) {}

    public function handle(): void
    {
        $report = Report::withoutGlobalScopes()->find($this->reportId);

        if (! $report) {
            return;
        }

        $requester = User::withoutGlobalScopes()->find($this->requestedByUserId);

        // تعيين سياق المستأجر لفحص Spatie داخل الـ queue worker (لا middleware هنا).
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($report->organization_id);

        // فحص ثانٍ للصلاحية عند التنفيذ الفعلي (لا يكفي فحص وقت الطلب فقط).
        if (! $requester || ! $requester->can('reports.generate')) {
            $report->update(['status' => ReportStatus::Failed]);

            return;
        }

        $report->update(['status' => ReportStatus::Processing]);

        try {
            $payload = $this->buildReportPayload($report);

            $filePath = "reports/{$report->organization_id}/{$report->id}.json";

            Storage::disk('local')->put($filePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $report->update([
                'status' => ReportStatus::Completed,
                'file_path' => $filePath,
                'expires_at' => now()->addDays(14),
            ]);
        } catch (\Throwable $exception) {
            $report->update(['status' => ReportStatus::Failed]);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportPayload(Report $report): array
    {
        $parameters = $report->parameters ?? [];

        $query = Recitation::withoutGlobalScopes()
            ->where('organization_id', $report->organization_id);

        if (! empty($parameters['student_id'])) {
            $query->where('student_id', $parameters['student_id']);
        }

        if (! empty($parameters['circle_id'])) {
            $query->where('circle_id', $parameters['circle_id']);
        }

        return [
            'report_type' => $report->report_type,
            'organization_id' => $report->organization_id,
            'generated_at' => now()->toIso8601String(),
            'total_recitations' => $query->count(),
        ];
    }
}
