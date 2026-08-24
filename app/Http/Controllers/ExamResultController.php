<?php

namespace App\Http\Controllers;

use App\Actions\CorrectExamResultAction;
use App\Enums\ExamResultStatus;
use App\Http\Requests\CorrectExamResultRequest;
use App\Http\Requests\StoreExamResultRequest;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Notifications\ExamResultNotification;
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
    ) {}

    public function store(StoreExamResultRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = ExamResult::create([
            'exam_id' => $data['exam_id'],
            'exam_section_id' => $data['exam_section_id'],
            'student_id' => $data['student_id'],
            'score' => $data['score'],
            'grade' => $data['grade'] ?? null,
            'examiner_id' => $request->user()->id,
            'status' => $data['status'],
        ]);

        return response()->json([
            'data' => $result,
        ], 201);
    }

    public function approve(Request $request, ExamResult $examResult): JsonResponse
    {
        $this->authorize('approve', $examResult);

        if ($examResult->status === ExamResultStatus::Approved) {
            return response()->json([
                'data' => $examResult,
            ]);
        }

        $examResult->update([
            'status' => ExamResultStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $examResult->student?->user?->notify(new ExamResultNotification(
            studentId: $examResult->student_id,
            examResultId: $examResult->id,
            grade: (string) ($examResult->grade ?? $examResult->score),
        ));

        return response()->json([
            'data' => $examResult,
        ]);
    }

    public function correct(CorrectExamResultRequest $request, ExamResult $examResult): JsonResponse
    {
        $result = $this->correctExamResult->execute(
            $examResult,
            $request->validated(),
            $request->user(),
            $request,
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * صفحة نتائج امتحان محدد (Inertia) — تُستخدم للاعتماد ورصد الدرجات.
     */
    public function page(Request $request, Exam $exam): Response
    {
        $this->authorize('view', $exam);

        return Inertia::render('Admin/Exams/Results', [
            'exam' => $exam->load('sections'),
            'results' => $exam->results()->with('student.user')->latest()->paginate(30)->withQueryString(),
        ]);
    }
}
