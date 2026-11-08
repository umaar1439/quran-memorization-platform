<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStudentGoalAction;
use App\Actions\UpdateStudentGoalAction;
use App\Http\Requests\StoreStudentGoalRequest;
use App\Http\Requests\UpdateStudentGoalRequest;
use App\Http\Resources\StudentGoalResource;
use App\Models\StudentGoal;
use App\Queries\StudentGoalQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * store: إنشاء هدف (Teacher/Supervisor/Org-Admin، عبر StoreStudentGoalRequest
 * + Policy). index: أهداف الطالب بحسب نطاق المستخدم. update: مسموح فقط
 * قبل الاكتمال (status=active) — §5 دورة حياة البيانات.
 */
class StudentGoalController extends Controller
{
    public function __construct(
        private readonly StudentGoalQuery $goals,
        private readonly CreateStudentGoalAction $createGoal,
        private readonly UpdateStudentGoalAction $updateGoal,
    ) {}

    public function store(StoreStudentGoalRequest $request): JsonResponse
    {
        $goal = $this->createGoal->execute($request->validated());

        return (new StudentGoalResource($goal))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StudentGoal::class);

        return StudentGoalResource::collection(
            $this->goals->paginate(
                $request->user(),
                $request->filled('student_id') ? $request->integer('student_id') : null,
            ),
        );
    }

    public function update(UpdateStudentGoalRequest $request, StudentGoal $studentGoal): JsonResponse
    {
        $studentGoal = $this->updateGoal->execute($studentGoal, $request->validated());

        return (new StudentGoalResource($studentGoal))->response();
    }

    /**
     * صفحة أهداف الطالب الحالي (Inertia) — لدور student فقط، أهدافه هو.
     */
    public function page(Request $request): Response
    {
        $student = $request->user()->studentProfile;

        abort_if(! $student, 404, __('messages.students.missing_profile'));

        $this->authorize('view', $student);

        return Inertia::render('Student/Goals', [
            'goals' => $this->goals->forStudent($student->id),
        ]);
    }
}
