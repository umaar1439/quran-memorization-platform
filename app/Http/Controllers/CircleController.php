<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCircleAction;
use App\Actions\DeleteCircleAction;
use App\Actions\UpdateCircleAction;
use App\Http\Requests\StoreCircleRequest;
use App\Models\Circle;
use App\Queries\CircleDashboardQuery;
use App\Queries\CircleDirectoryQuery;
use App\Queries\MosqueOptionsQuery;
use App\Queries\UserOptionsQuery;
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
    public function __construct(
        private readonly CircleDirectoryQuery $circles,
        private readonly MosqueOptionsQuery $mosqueOptions,
        private readonly UserOptionsQuery $userOptions,
        private readonly CreateCircleAction $createCircle,
        private readonly UpdateCircleAction $updateCircle,
        private readonly DeleteCircleAction $deleteCircle,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Circle::class);

        return Inertia::render('Admin/Circles/Index', [
            'circles' => $this->circles->paginate(
                $request->string('search')->toString() ?: null,
                $request->filled('mosque_id') ? $request->integer('mosque_id') : null,
                $request->user(),
            ),
            'mosques' => $this->mosqueOptions->all(),
            'teachers' => $this->userOptions->byRole('teacher'),
            'filters' => $request->only('search', 'mosque_id'),
        ]);
    }

    /**
     * لوحة تحكم تفصيلية للحلقة (Circle Executive Dashboard) — ملخص عام،
     * بيانات المعلم، قائمة الطلاب، والطالب المتميز — بلا استعلامات N+1.
     * حماية IDOR تتم عبر Global Scope (404) + CirclePolicy::view (403).
     */
    public function show(Circle $circle, CircleDashboardQuery $dashboard): Response
    {
        $this->authorize('view', $circle);

        return Inertia::render('Admin/Circles/Show', $dashboard->build($circle));
    }

    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $this->createCircle->execute($request->validated(), $request->user());

        return back()->with('success', __('messages.circles.created'));
    }

    public function update(StoreCircleRequest $request, Circle $circle): RedirectResponse
    {
        $this->updateCircle->execute($circle, $request->validated());

        return back()->with('success', __('messages.circles.updated'));
    }

    public function destroy(Circle $circle): RedirectResponse
    {
        $this->authorize('delete', $circle);

        $this->deleteCircle->execute($circle);

        return back()->with('success', __('messages.circles.deleted'));
    }
}
