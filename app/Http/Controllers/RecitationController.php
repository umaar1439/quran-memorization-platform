<?php

namespace App\Http\Controllers;

use App\Actions\RecordRecitationAction;
use App\Http\Requests\StoreRecitationRequest;
use App\Models\Recitation;
use App\Models\StudentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: تسجيل تسميع (Teacher فقط، عبر StoreRecitationRequest + Policy + Action).
 * index: تسميعات الحلقة (Teacher) أو تسميعات الطالب (Student/Parent).
 * show: تفاصيل تسميع واحد مع أخطائه.
 */
class RecitationController extends Controller
{
    public function __construct(
        private readonly RecordRecitationAction $recordRecitation,
    ) {}

    public function store(StoreRecitationRequest $request): JsonResponse
    {
        $recitation = $this->recordRecitation->execute(
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'data' => $recitation->load('errors'),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Recitation::class);

        $user = $request->user();

        $query = Recitation::query()->with('errors');

        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('student')) {
            $studentProfile = $user->studentProfile;
            $query->where('student_id', $studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $guardian = $user->guardian;
            $studentIds = $guardian
                ? $guardian->students()->pluck('student_profiles.id')
                : collect();
            $query->whereIn('student_id', $studentIds);
        }

        return response()->json([
            'data' => $query->latest('performed_at')->paginate(20),
        ]);
    }

    public function show(Recitation $recitation): JsonResponse
    {
        $this->authorize('view', $recitation);

        return response()->json([
            'data' => $recitation->load('errors'),
        ]);
    }

    /**
     * صفحة "تسميع سريع" (Teacher) — نموذج تسجيل تسميع جديد + آخر تسميعات
     * المعلم لطلاب حلقته.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Recitation::class);

        $user = $request->user();

        $students = StudentProfile::query()
            ->with('user')
            ->when(
                $user->hasRole('teacher'),
                fn ($query) => $query->whereHas('circle', fn ($q) => $q->where('teacher_id', $user->id)),
            )
            ->get();

        $recentRecitations = Recitation::query()
            ->with(['errors', 'student.user'])
            ->where('teacher_id', $user->id)
            ->latest('performed_at')
            ->limit(15)
            ->get();

        return Inertia::render('Teacher/QuickRecitation', [
            'students' => $students,
            'recentRecitations' => $recentRecitations,
        ]);
    }
}
