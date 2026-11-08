<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ApproveExamResultAction;
use App\Actions\CorrectExamResultAction;
use App\Actions\CreateExamResultAction;
use App\Http\Requests\CorrectExamResultRequest;
use App\Http\Requests\StoreExamResultRequest;
use App\Http\Resources\ExamResultResource;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Queries\ExamResultDirectoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: تسجيل نتيجة طالب (draft/submitted). approve: اعتماد النتيجة
 * (org-admin/supervisor فقط) → status=approved + approved_by + approved_at.
 * correct: تصحيح نتيجة معتمدة عبر Correction Workflow (§5 دورة حياة
 * البيانات — الثبات بعد الاعتماد، لا تعديل مباشر).
 */
class ExamResultController extends Controller
{
    public function __construct(
        private readonly CorrectExamResultAction $correctExamResult,
        private readonly CreateExamResultAction $createExamResult,
        private readonly ApproveExamResultAction $approveExamResult,
        private readonly ExamResultDirectoryQuery $results,
    ) {}

    public function store(StoreExamResultRequest $request): JsonResponse
    {
        $result = $this->createExamResult->execute(
            $request->validated(),
            $request->user(),
        );

        return (new ExamResultResource($result))->response()->setStatusCode(201);
    }

    public function approve(Request $request, ExamResult $examResult): JsonResponse
    {
        $this->authorize('approve', $examResult);

        $examResult = $this->approveExamResult->execute($examResult, $request->user());

        return (new ExamResultResource($examResult))->response();
    }

    public function correct(CorrectExamResultRequest $request, ExamResult $examResult): JsonResponse
    {
        $result = $this->correctExamResult->execute(
            $examResult,
            $request->validated(),
            $request->user(),
            $request,
        );

        return (new ExamResultResource($result))->response();
    }

    /**
     * صفحة نتائج امتحان محدد (Inertia) — تُستخدم للاعتماد ورصد الدرجات.
     */
    public function page(Request $request, Exam $exam): Response
    {
        $this->authorize('view', $exam);

        return Inertia::render('Admin/Exams/Results', [
            'exam' => $exam->load('sections'),
            'results' => $this->results->forExam($exam),
        ]);
    }
}
