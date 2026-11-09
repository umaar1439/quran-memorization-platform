<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTeacherAction;
use App\Actions\DeleteTeacherAction;
use App\Actions\UpdateTeacherAction;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\TeacherProfile;
use App\Queries\CircleOptionsQuery;
use App\Queries\TeacherDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * إدارة المعلمين (Teacher Management) منفصلة عن إدارة المستخدمين العامة.
 * كل معلم = حساب User بدور teacher + TeacherProfile + ربط إلزامي بحلقة واحدة
 * على الأقل. العزل يتم عبر Global Scope (404) وسياسة TeacherProfilePolicy (403).
 *
 * الـ Controller رقيق: تفويض القراءة إلى Query والكتابة إلى Actions.
 */
class TeacherController extends Controller
{
    public function __construct(
        private readonly TeacherDirectoryQuery $teachers,
        private readonly CircleOptionsQuery $circles,
        private readonly CreateTeacherAction $createTeacher,
        private readonly UpdateTeacherAction $updateTeacher,
        private readonly DeleteTeacherAction $deleteTeacher,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', TeacherProfile::class);

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => $this->teachers->paginate($request->string('search')->toString() ?: null),
            'circles' => $this->circles->all(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $this->createTeacher->execute($request->toData(), $request->user());

        return back()->with('success', __('messages.teachers.created'));
    }

    public function update(UpdateTeacherRequest $request, TeacherProfile $teacher): RedirectResponse
    {
        $this->updateTeacher->execute($teacher, $request->toData());

        return back()->with('success', __('messages.teachers.updated'));
    }

    public function destroy(TeacherProfile $teacher): RedirectResponse
    {
        $this->authorize('delete', $teacher);

        $this->deleteTeacher->execute($teacher);

        return back()->with('success', __('messages.teachers.deleted'));
    }
}
