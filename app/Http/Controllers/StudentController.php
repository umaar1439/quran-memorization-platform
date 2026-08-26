<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Circle;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * إدارة الطلاب (Student Management) منفصلة عن إدارة المستخدمين العامة.
 * كل طالب = حساب User بدور student + StudentProfile مربوط بحلقة. العزل عبر
 * Global Scope (404) وسياسة StudentProfilePolicy (403).
 */
class StudentController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', StudentProfile::class);

        $query = StudentProfile::query()->with(['user', 'circle', 'mosque']);

        if ($request->filled('search')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->string('search') . '%');
            });
        }

        if ($request->filled('circle_id')) {
            $query->where('circle_id', $request->integer('circle_id'));
        }

        return Inertia::render('Admin/Students/Index', [
            'students' => $query->latest()->paginate(20)->withQueryString(),
            'circles' => Circle::query()->with('mosque:id,name')->orderBy('name')->get(['id', 'name', 'mosque_id']),
            'filters' => $request->only('search', 'circle_id'),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $organizationId = $request->user()->organization_id;

        $circle = Circle::findOrFail($data['circle_id']);

        $user = User::create([
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole('student');

        StudentProfile::create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'mosque_id' => $circle->mosque_id,
            'circle_id' => $circle->id,
            'enrollment_number' => $data['enrollment_number'],
            'birth_date' => $data['birth_date'] ?? null,
            'current_juz' => $data['current_juz'] ?? null,
            'status' => $data['status'],
        ]);

        return back()->with('success', 'تم إضافة الطالب بنجاح.');
    }

    public function update(UpdateStudentRequest $request, StudentProfile $student): RedirectResponse
    {
        $data = $request->validated();

        $circle = Circle::findOrFail($data['circle_id']);

        $student->user?->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $student->update([
            'mosque_id' => $circle->mosque_id,
            'circle_id' => $circle->id,
            'enrollment_number' => $data['enrollment_number'],
            'birth_date' => $data['birth_date'] ?? null,
            'current_juz' => $data['current_juz'] ?? null,
            'status' => $data['status'],
        ]);

        return back()->with('success', 'تم تحديث بيانات الطالب بنجاح.');
    }

    public function destroy(StudentProfile $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $user = $student->user;

        $student->delete();

        if ($user) {
            $user->delete();
        }

        return back()->with('success', 'تم حذف الطالب بنجاح.');
    }
}
