<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateStudentAction;
use App\Actions\DeleteStudentAction;
use App\Actions\UpdateStudentAction;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\StudentProfile;
use App\Queries\CircleOptionsQuery;
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
        private readonly CreateStudentAction $createStudent,
        private readonly UpdateStudentAction $updateStudent,
        private readonly DeleteStudentAction $deleteStudent,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentProfile::class);

        return Inertia::render('Admin/Students/Index', [
            'students' => $this->students->paginate(
                $request->string('search')->toString() ?: null,
                $request->filled('circle_id') ? $request->integer('circle_id') : null,
            ),
            'circles' => $this->circles->all(),
            'filters' => $request->only('search', 'circle_id'),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->createStudent->execute($request->toDto(), $request->user());

        return back()->with('success', __('messages.students.created'));
    }

    public function update(UpdateStudentRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->updateStudent->execute($student, $request->toDto());

        return back()->with('success', __('messages.students.updated'));
    }

    public function destroy(StudentProfile $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $this->deleteStudent->execute($student);

        return back()->with('success', __('messages.students.deleted'));
    }
}
