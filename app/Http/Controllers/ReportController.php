<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DownloadReportAction;
use App\Actions\GenerateReportAction;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Queries\ReportDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
        private readonly DownloadReportAction $downloadReport,
        private readonly ReportDirectoryQuery $reports,
    ) {}

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->generateReport->execute(
            $request->validated(),
            $request->user(),
        );

        return (new ReportResource($report))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Report::class);

        return ReportResource::collection($this->reports->paginate());
    }

    public function download(Report $report): StreamedResponse
    {
        $this->authorize('download', $report);

        return $this->downloadReport->execute($report);
    }

    /**
     * صفحة قائمة تقارير المستخدم الحالي مع نموذج طلب تقرير جديد (Inertia).
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $this->reports->paginate(withQueryString: true),
        ]);
    }
}
