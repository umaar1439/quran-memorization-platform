<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تحميل ملف شهادة من الـ Private Disk. التفويض يحدث في الـ Controller
 * عبر CertificatePolicy::view قبل تنفيذ هذه الخطوة.
 */
class DownloadCertificateAction
{
    public function execute(Certificate $certificate): StreamedResponse
    {
        $filePath = $certificate->getRawOriginal('file_path');

        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404);
        }

        return Storage::disk('local')->response($filePath);
    }
}
