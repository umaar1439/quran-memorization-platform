<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMosqueRequest;
use App\Models\Mosque;
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
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', Mosque::class);

        $query = Mosque::query()->withCount('circles');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        return Inertia::render('Admin/Mosques/Index', [
            'mosques' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreMosqueRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;

        Mosque::create($data);

        return back()->with('success', 'تم إضافة المسجد بنجاح.');
    }

    public function update(StoreMosqueRequest $request, Mosque $mosque): RedirectResponse
    {
        $mosque->update($request->validated());

        return back()->with('success', 'تم تحديث بيانات المسجد بنجاح.');
    }

    public function destroy(Mosque $mosque): RedirectResponse
    {
        $this->authorize('delete', $mosque);

        $mosque->delete();

        return back()->with('success', 'تم حذف المسجد بنجاح.');
    }
}
