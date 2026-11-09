<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCircleAction;
use App\Actions\DeleteCircleAction;
use App\Actions\UpdateCircleAction;
use App\Http\Requests\StoreCircleRequest;
use App\Models\Circle;
use App\Models\User;
use App\Queries\CircleDashboardQuery;
use App\Queries\CircleDirectoryQuery;
use App\Queries\MosqueOptionsQuery;
use App\Queries\UserOptionsQuery;
use App\Support\RoleHierarchy;
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

        /** @var User $actor */
        $actor = $request->user();

        return Inertia::render('Admin/Circles/Index', [
            'circles' => $this->circles->paginate(
                $request->string('search')->toString() ?: null,
                $request->filled('mosque_id') ? $request->integer('mosque_id') : null,
                $actor,
            ),
            'mosques' => $this->mosqueOptions->all(),
            // أدوار مسؤول الحلقة فقط، ومقيَّدة بمنظمة الفاعل داخل الاستعلام
            // (User بلا Global Scope) — وبنفس قائمة StoreCircleRequest.
            'teachers' => $this->userOptions->byRoles(RoleHierarchy::CIRCLE_TEACHER_ROLES, $actor),
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
        /** @var User $actor */
        $actor = $request->user();

        $this->createCircle->execute($request->toData(), $actor);

        return back()->with('success', __('messages.circles.created'));
    }

    public function update(StoreCircleRequest $request, Circle $circle): RedirectResponse
    {
        $this->updateCircle->execute($circle, $request->toData());

        return back()->with('success', __('messages.circles.updated'));
    }

    public function destroy(Circle $circle): RedirectResponse
    {
        $this->authorize('delete', $circle);

        $this->deleteCircle->execute($circle);

        return back()->with('success', __('messages.circles.deleted'));
    }
}
