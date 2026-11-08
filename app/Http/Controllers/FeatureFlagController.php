<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateFeatureFlagAction;
use App\Http\Requests\UpdateFeatureFlagRequest;
use App\Models\FeatureFlag;
use App\Queries\FeatureFlagQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة أعلام الميزات. Org Admin يرى ويُبدّل فقط أعلام self-service
 * (FeatureFlagPolicy::SELF_SERVICE_FLAGS) لمنظمته. Super Admin يرى ويُبدّل
 * كل الأعلام العالمية والخاصة بالمنظمات (§3، §6.4).
 */
class FeatureFlagController extends Controller
{
    public function __construct(
        private readonly UpdateFeatureFlagAction $updateFlag,
        private readonly FeatureFlagQuery $flags,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('viewAny', FeatureFlag::class);

        $user = $request->user();

        return Inertia::render('Admin/FeatureFlags/Index', [
            'flags' => $this->flags->forUser($user),
            'isSuperAdmin' => $user->hasRole('super-admin'),
        ]);
    }

    public function update(UpdateFeatureFlagRequest $request, FeatureFlag $flag): RedirectResponse
    {
        $this->updateFlag->execute($flag, $request->enabled(), $request->user());

        return back()->with('success', __('messages.feature_flags.updated'));
    }
}
