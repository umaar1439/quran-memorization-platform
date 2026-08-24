<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Requests\UpdateSettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة إعدادات المنظمة (المصطلحات الديناميكية). manage فقط لـ org-admin/super-admin
 * (SettingPolicy). يُبطِل ذاكرة التخزين المؤقت المشتركة عبر Inertia (§7) عند الحفظ.
 */
class SettingsController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('manage', Setting::class);

        $organizationId = $request->user()->organization_id;

        return Inertia::render('Admin/Settings/Index', [
            'settings' => Setting::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->orderBy('group')
                ->orderBy('key')
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage', Setting::class);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:120', Rule::in(UpdateSettingRequest::ALLOWED_KEYS)],
            'settings.*.value' => ['nullable', 'string', 'max:2000'],
            'settings.*.group' => ['nullable', 'string', 'max:80'],
        ]);

        $organizationId = $request->user()->organization_id;

        foreach ($validated['settings'] as $item) {
            Setting::withoutGlobalScopes()->updateOrCreate(
                ['organization_id' => $organizationId, 'key' => $item['key']],
                ['value' => $item['value'] ?? null, 'group' => $item['group'] ?? null],
            );
        }

        Cache::forget("settings:{$organizationId}");

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
