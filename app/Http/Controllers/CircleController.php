<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCircleRequest;
use App\Models\Circle;
use App\Models\Mosque;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحات إدارة الحلقات (Inertia). page: قائمة مع فلترة بحسب المسجد.
 * store/update تتحقق (عبر StoreCircleRequest) أن mosque_id/teacher_id
 * ينتميان لمنظمة المستخدم الحالي (IDOR Defense).
 */
class CircleController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Circle::class);

        $query = Circle::query()->with(['mosque', 'teacher'])->withCount('studentProfiles');

        if ($request->filled('mosque_id')) {
            $query->where('mosque_id', $request->integer('mosque_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        return Inertia::render('Admin/Circles/Index', [
            'circles' => $query->latest()->paginate(20)->withQueryString(),
            'mosques' => Mosque::query()->orderBy('name')->get(['id', 'name']),
            'teachers' => User::query()->role('teacher')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'mosque_id'),
        ]);
    }

    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        Circle::create($data);

        return back()->with('success', 'تم إضافة الحلقة بنجاح.');
    }

    public function update(StoreCircleRequest $request, Circle $circle): RedirectResponse
    {
        $circle->update($request->validated());

        return back()->with('success', 'تم تحديث بيانات الحلقة بنجاح.');
    }

    public function destroy(Circle $circle): RedirectResponse
    {
        $this->authorize('delete', $circle);

        $circle->delete();

        return back()->with('success', 'تم حذف الحلقة بنجاح.');
    }
}
