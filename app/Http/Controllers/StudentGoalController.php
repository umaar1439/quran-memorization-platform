<?php

namespace App\Http\Controllers;

use App\Enums\GoalStatus;
use App\Http\Requests\StoreStudentGoalRequest;
use App\Http\Requests\UpdateStudentGoalRequest;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use App\Notifications\GoalCompletionNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: إنشاء هدف (Teacher/Supervisor/Org-Admin، عبر StoreStudentGoalRequest
 * + Policy). index: أهداف الطالب بحسب نطاق المستخدم. update: مسموح فقط
 * قبل الاكتمال (status=active) — §5 دورة حياة البيانات.
 */
class StudentGoalController extends Controller
{
    public function store(StoreStudentGoalRequest $request): JsonResponse
    {
        $data = $request->validated();

        $student = StudentProfile::withoutGlobalScopes()->findOrFail($data['student_id']);

        $goal = StudentGoal::withoutGlobalScopes()->create([
            'organization_id' => $student->organization_id,
            'student_id' => $student->id,
            'period_type' => $data['period_type'],
            'target_type' => $data['target_type'],
            'surah_id' => $data['surah_id'] ?? null,
            'juz_number' => $data['juz_number'] ?? null,
            'target_amount' => $data['target_amount'],
            'achieved_amount' => 0,
            'completion_percentage' => 0,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => GoalStatus::Active,
        ]);

        return response()->json([
            'data' => $goal,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', StudentGoal::class);

        $user = $request->user();

        $query = StudentGoal::query()->with('surah');

        if ($user->hasRole('teacher') && ! $user->hasRole('supervisor') && ! $user->hasRole('org-admin') && ! $user->hasRole('super-admin')) {
            $query->whereHas('student.circle', fn ($q) => $q->where('teacher_id', $user->id));
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

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }

        return response()->json([
            'data' => $query->latest('period_start')->paginate(20),
        ]);
    }

    public function update(UpdateStudentGoalRequest $request, StudentGoal $studentGoal): JsonResponse
    {
        $wasCompleted = $studentGoal->status === GoalStatus::Completed;

        $studentGoal->update($request->validated());

        // إشعار إكمال هدف عند الانتقال الفعلي إلى status=completed (Phase 13 نقطة 3).
        if (! $wasCompleted && $studentGoal->status === GoalStatus::Completed) {
            $studentGoal->student?->user?->notify(new GoalCompletionNotification(
                studentId: $studentGoal->student_id,
                goalId: $studentGoal->id,
                description: 'تم إكمال الهدف بنجاح.',
            ));
        }

        return response()->json([
            'data' => $studentGoal,
        ]);
    }

    /**
     * صفحة أهداف الطالب الحالي (Inertia) — لدور student فقط، أهدافه هو.
     */
    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, 'لا يوجد ملف طالب مرتبط بهذا المستخدم.');

        $this->authorize('view', $student);

        return Inertia::render('Student/Goals', [
            'goals' => StudentGoal::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->latest('period_start')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
