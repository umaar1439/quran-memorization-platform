<?php

namespace App\Http\Controllers;

use App\Actions\GenerateReportAction;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * store: طلب تقرير جديد (حالة queued فوراً، التوليد عبر Queue Job).
 * index: قائمة التقارير ضمن نطاق المستخدم. download: تحميل من private
 * storage (auth + tenant + policy + expiration check) — §4 تهديد #11.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly GenerateReportAction $generateReport,
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->generateReport->execute(
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'data' => $report,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::query();

        return response()->json([
            'data' => $query->latest('created_at')->paginate(20),
        ]);
    }

    public function download(Report $report): StreamedResponse
    {
        $this->authorize('download', $report);

        if ($report->expires_at && $report->expires_at->isPast()) {
            abort(410, 'انتهت صلاحية هذا التقرير.');
        }

        $filePath = $report->getRawOriginal('file_path');

        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404, 'ملف التقرير غير موجود.');
        }

        return Storage::disk('local')->response($filePath);
    }

    /**
     * صفحة قائمة تقارير المستخدم الحالي مع نموذج طلب تقرير جديد (Inertia).
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => Report::query()->latest('created_at')->paginate(20)->withQueryString(),
        ]);
    }
}
