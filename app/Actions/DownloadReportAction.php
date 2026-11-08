<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Report;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تحميل ملف تقرير من الـ Private Disk بعد التحقق من الصلاحية (في
 * الـ Controller) ومن الصلاحية الزمنية ووجود الملف — §4 تهديد #11.
 */
class DownloadReportAction
{
    public function execute(Report $report): StreamedResponse
    {
        if ($report->expires_at && $report->expires_at->isPast()) {
            abort(410, __('messages.reports.expired'));
        }

        $filePath = $report->getRawOriginal('file_path');

        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404, __('messages.reports.file_missing'));
        }

        return Storage::disk('local')->response($filePath);
    }
}
