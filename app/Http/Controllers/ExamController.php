<?php

namespace App\Http\Controllers;

use App\Enums\ExamStatus;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Models\Circle;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Mosque;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function store(StoreExamRequest $request): JsonResponse
    {
        $data = $request->validated();

        $exam = DB::transaction(function () use ($data, $request) {
            $exam = Exam::create([
                'mosque_id' => $data['mosque_id'] ?? null,
                'circle_id' => $data['circle_id'] ?? null,
                'title' => $data['title'],
                'exam_type' => $data['exam_type'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'status' => ExamStatus::Draft,
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['sections'] ?? [] as $section) {
                ExamSection::withoutGlobalScopes()->create([
                    'exam_id' => $exam->id,
                    'organization_id' => $exam->organization_id,
                    'surah_number' => $section['surah_number'],
                    'ayah_from' => $section['ayah_from'],
                    'ayah_to' => $section['ayah_to'],
                    'section_type' => $section['section_type'],
                    'max_score' => $section['max_score'],
                ]);
            }

            return $exam;
        });

        return response()->json([
            'data' => $exam->load('sections'),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::query();

        if ($request->filled('circle_id')) {
            $query->where('circle_id', $request->integer('circle_id'));
        }

        if ($request->filled('mosque_id')) {
            $query->where('mosque_id', $request->integer('mosque_id'));
        }

        return response()->json([
            'data' => $query->latest('scheduled_at')->paginate(20),
        ]);
    }

    public function show(Exam $exam): JsonResponse
    {
        $this->authorize('view', $exam);

        return response()->json([
            'data' => $exam->load(['sections', 'results']),
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): JsonResponse
    {
        $exam->update($request->validated());

        return response()->json([
            'data' => $exam,
        ]);
    }

    /**
     * صفحة قائمة الامتحانات (Inertia) لجميع الأدوار — النطاق نفسه المطبَّق
     * في index()، مع بيانات مساجد/حلقات لدعم نموذج الإنشاء.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Exam::class);

        $query = Exam::query()->with(['mosque', 'circle']);

        if ($request->filled('circle_id')) {
            $query->where('circle_id', $request->integer('circle_id'));
        }

        if ($request->filled('mosque_id')) {
            $query->where('mosque_id', $request->integer('mosque_id'));
        }

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $query->latest('scheduled_at')->paginate(20)->withQueryString(),
            'mosques' => Mosque::query()->orderBy('name')->get(['id', 'name']),
            'circles' => Circle::query()->orderBy('name')->get(['id', 'name', 'mosque_id']),
            'filters' => $request->only('circle_id', 'mosque_id'),
        ]);
    }
}
