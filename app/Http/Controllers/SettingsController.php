<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateSettingsAction;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Queries\SettingsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * صفحة إعدادات المنظمة (المصطلحات الديناميكية). manage فقط لـ org-admin/super-admin
 * (SettingPolicy). الحفظ يتم دفعة واحدة عبر UpdateSettingsAction مع إبطال الذاكرة (§7).
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly UpdateSettingsAction $updateSettings,
        private readonly SettingsQuery $settings,
    ) {}

    public function page(Request $request): Response
    {
        $this->authorize('manage', Setting::class);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->settings->allForOrganization($request->user()->organization_id),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $this->updateSettings->execute(
            (int) $request->user()->organization_id,
            $request->settings(),
        );

        return back()->with('success', __('messages.settings.saved'));
    }
}
