<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Circle;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * إدارة المعلمين (Teacher Management) منفصلة عن إدارة المستخدمين العامة.
 * كل معلم = حساب User بدور teacher + TeacherProfile + ربط إلزامي بحلقة واحدة
 * على الأقل. العزل يتم عبر Global Scope (404) وسياسة TeacherProfilePolicy (403).
 */
class TeacherController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', TeacherProfile::class);

        $query = TeacherProfile::query()->with(['user', 'user.circlesTaught']);

        if ($request->filled('search')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->string('search') . '%');
            });
        }

        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => $query->latest()->paginate(20)->withQueryString(),
            'circles' => Circle::query()->with('mosque:id,name')->orderBy('name')->get(['id', 'name', 'mosque_id']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $organizationId = $request->user()->organization_id;

        $user = $this->resolveTeacherUser($data, $organizationId);
        $user->assignRole('teacher');

        TeacherProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'organization_id' => $organizationId,
                'specialization' => $data['specialization'] ?? null,
                'ijazah_details' => $data['ijazah_details'] ?? null,
                'hired_at' => $data['hired_at'] ?? null,
            ]
        );

        $this->syncCircles($user, $data['circles'], $organizationId);

        return back()->with('success', 'تم إضافة المعلم بنجاح.');
    }

    public function update(UpdateTeacherRequest $request, TeacherProfile $teacher): RedirectResponse
    {
        $data = $request->validated();
        $organizationId = $request->user()->organization_id;

        $user = $teacher->user;
        abort_if(! $user, 404, 'لا يوجد حساب مرتبط بهذا المعلم.');

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $teacher->update([
            'specialization' => $data['specialization'] ?? null,
            'ijazah_details' => $data['ijazah_details'] ?? null,
            'hired_at' => $data['hired_at'] ?? null,
        ]);

        $this->syncCircles($user, $data['circles'], $organizationId);

        return back()->with('success', 'تم تحديث بيانات المعلم بنجاح.');
    }

    public function destroy(TeacherProfile $teacher): RedirectResponse
    {
        $this->authorize('delete', $teacher);

        $user = $teacher->user;

        if ($user) {
            // إلغاء ربط الحلقات قبل حذف الحساب.
            Circle::where('teacher_id', $user->id)->update(['teacher_id' => null]);
        }

        $teacher->delete();

        if ($user) {
            $user->delete();
        }

        return back()->with('success', 'تم حذف المعلم بنجاح.');
    }

    /**
     * إنشاء حساب معلم جديد أو ربط حساب موجود (ضمن نفس المنظمة فقط).
     */
    private function resolveTeacherUser(array $data, int $organizationId): User
    {
        if (! empty($data['user_id'])) {
            $user = User::findOrFail($data['user_id']);

            abort_if($user->organization_id !== $organizationId, 403, 'لا يمكن ربط حساب من منظمة أخرى.');

            $user->update([
                'status' => $data['status'],
                'phone' => $data['phone'] ?? $user->phone,
            ]);

            return $user;
        }

        return User::create([
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * ربط المعلم بالحلقات المختارة (إلزامي: حلقة واحدة على الأقل). كل الحلقات
     * تُفلتر حصراً على منظمة المسؤول الحالي لمنع التسرب عابر المنظمات.
     */
    private function syncCircles(User $user, array $circleIds, int $organizationId): void
    {
        $validCircleIds = Circle::whereIn('id', $circleIds)
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->all();

        // إلغاء الربط القديم ثم ربط الحلقات الجديدة.
        Circle::where('teacher_id', $user->id)->update(['teacher_id' => null]);
        Circle::whereIn('id', $validCircleIds)->update(['teacher_id' => $user->id]);
    }
}
