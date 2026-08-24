<?php

namespace App\Http\Controllers;

use App\Models\FeatureFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة أعلام الميزات. Org Admin يرى ويُبدّل فقط أعلام self-service
 * (FeatureFlagPolicy::SELF_SERVICE_FLAGS) لمنظمته. Super Admin يرى ويُبدّل
 * كل الأعلام العالمية والخاصة بالمنظمات (§3، §6.4).
 */
class FeatureFlagController extends Controller
{
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', FeatureFlag::class);

        $user = $request->user();

        $query = FeatureFlag::withoutGlobalScopes();

        if (! $user->hasRole('super-admin')) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('organization_id')
                    ->orWhere('organization_id', $user->organization_id);
            })->whereIn('key', \App\Policies\FeatureFlagPolicy::SELF_SERVICE_FLAGS);
        }

        return Inertia::render('Admin/FeatureFlags/Index', [
            'flags' => $query->orderBy('key')->get(),
            'isSuperAdmin' => $user->hasRole('super-admin'),
        ]);
    }

    public function update(Request $request, FeatureFlag $flag): RedirectResponse
    {
        $this->authorize('update', $flag);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $flag->update([
            'enabled' => $validated['enabled'],
            'updated_by' => $request->user()->id,
        ]);

        Cache::forget("feature-flags:{$flag->organization_id}:all");
        Cache::forget('feature-flags:' . $request->user()->organization_id . ':all');

        return back()->with('success', 'تم تحديث حالة الميزة بنجاح.');
    }
}
