<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateExamAction;
use App\Actions\UpdateExamAction;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Queries\CircleOptionsQuery;
use App\Queries\ExamDirectoryQuery;
use App\Queries\MosqueOptionsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: إنشاء امتحان (يبدأ draft دوماً) مع أقسامه الاختيارية.
 * index: قائمة الامتحانات ضمن نطاق المستخدم (Global Scope + Policy).
 * show: تفاصيل مع sections و results.
 * update: مسموح فقط قبل status=approved (§5 دورة حياة البيانات).
 */
class ExamController extends Controller
{
    public function __construct(
        private readonly ExamDirectoryQuery $exams,
        private readonly MosqueOptionsQuery $mosqueOptions,
        private readonly CircleOptionsQuery $circles,
        private readonly CreateExamAction $createExam,
        private readonly UpdateExamAction $updateExam,
    ) {}

    public function store(StoreExamRequest $request): JsonResponse
    {
        $exam = $this->createExam->execute($request->toData(), $request->user());

        return (new ExamResource($exam->load('sections')))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Exam::class);

        return ExamResource::collection(
            $this->exams->paginate(
                $request->filled('circle_id') ? $request->integer('circle_id') : null,
                $request->filled('mosque_id') ? $request->integer('mosque_id') : null,
            ),
        );
    }

    public function show(Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        return (new ExamResource($exam->load(['sections', 'results'])))->response();
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $exam = $this->updateExam->execute($exam, $request->toData());

        return (new ExamResource($exam))->response();
    }

    /**
     * صفحة قائمة الامتحانات (Inertia) لجميع الأدوار — النطاق نفسه المطبَّق
     * في index()، مع بيانات مساجد/حلقات لدعم نموذج الإنشاء.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Exam::class);

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $this->exams->paginate(
                $request->filled('circle_id') ? $request->integer('circle_id') : null,
                $request->filled('mosque_id') ? $request->integer('mosque_id') : null,
            )->withQueryString(),
            'mosques' => $this->mosqueOptions->all(),
            'circles' => $this->circles->all(),
            'filters' => $request->only('circle_id', 'mosque_id'),
        ]);
    }
}
