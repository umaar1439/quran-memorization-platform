<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateMosqueAction;
use App\Actions\DeleteMosqueAction;
use App\Actions\UpdateMosqueAction;
use App\Http\Requests\StoreMosqueRequest;
use App\Models\Mosque;
use App\Models\User;
use App\Queries\MosqueDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحات إدارة المساجد (Inertia). page: قائمة مع بحث/ترقيم. store/update/destroy:
 * عمليات CRUD تُعيد توجيهاً مع flash message. كل استعلام محصور ضمنياً بمنظمة
 * المستخدم عبر BelongsToOrganization Global Scope (باستثناء super-admin).
 */
class MosqueController extends Controller
{
    public function __construct(
        private readonly MosqueDirectoryQuery $mosques,
        private readonly CreateMosqueAction $createMosque,
        private readonly UpdateMosqueAction $updateMosque,
        private readonly DeleteMosqueAction $deleteMosque,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Mosque::class);

        return Inertia::render('Admin/Mosques/Index', [
            'mosques' => $this->mosques->paginate(
                $request->string('search')->toString() ?: null,
            ),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreMosqueRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->createMosque->execute($request->toData(), $actor);

        return back()->with('success', __('messages.mosques.created'));
    }

    public function update(StoreMosqueRequest $request, Mosque $mosque): RedirectResponse
    {
        $this->updateMosque->execute($mosque, $request->toData());

        return back()->with('success', __('messages.mosques.updated'));
    }

    public function destroy(Mosque $mosque): RedirectResponse
    {
        $this->authorize('delete', $mosque);

        $this->deleteMosque->execute($mosque);

        return back()->with('success', __('messages.mosques.deleted'));
    }
}
