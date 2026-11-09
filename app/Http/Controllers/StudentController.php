<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStudentAction;
use App\Actions\DeleteStudentAction;
use App\Actions\UpdateStudentAction;
use App\Enums\StudentStatus;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\StudentProfile;
use App\Models\User;
use App\Queries\CircleOptionsQuery;
use App\Queries\MosqueOptionsQuery;
use App\Queries\StudentDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * إدارة الطلاب (Student Management) منفصلة عن إدارة المستخدمين العامة.
 * كل طالب = حساب User بدور student + StudentProfile مربوط بحلقة. العزل عبر
 * Global Scope (404) وسياسة StudentProfilePolicy (403).
 *
 * الـ Controller رقيق: تفويض القراءة إلى Query والكتابة إلى Actions.
 */
class StudentController extends Controller
{
    public function __construct(
        private readonly StudentDirectoryQuery $students,
        private readonly CircleOptionsQuery $circles,
        private readonly MosqueOptionsQuery $mosques,
        private readonly CreateStudentAction $createStudent,
        private readonly UpdateStudentAction $updateStudent,
        private readonly DeleteStudentAction $deleteStudent,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentProfile::class);

        $status = StudentStatus::tryFrom($request->string('status')->toString());

        return Inertia::render('Admin/Students/Index', [
            'students' => $this->students->paginate(
                search: $request->string('search')->toString() ?: null,
                circleId: $request->filled('circle_id') ? $request->integer('circle_id') : null,
                mosqueId: $request->filled('mosque_id') ? $request->integer('mosque_id') : null,
                status: $status?->value,
            ),
            'circles' => $this->circles->all(),
            'mosques' => $this->mosques->all(),
            'filters' => $request->only('search', 'circle_id', 'mosque_id', 'status'),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->createStudent->execute($request->toData(), $actor);

        return back()->with('success', __('messages.students.created'));
    }

    public function update(UpdateStudentRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->updateStudent->execute($student, $request->toData());

        return back()->with('success', __('messages.students.updated'));
    }

    public function destroy(Request $request, StudentProfile $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        /** @var User $actor */
        $actor = $request->user();

        $this->deleteStudent->execute($student, $actor);

        return back()->with('success', __('messages.students.deleted'));
    }
}
