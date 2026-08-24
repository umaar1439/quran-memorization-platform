<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * generate/download — حسب الدور والنطاق (Super Admin/Org Admin كل التقارير
 * في نطاقهم، Supervisor مساجده، Teacher تقرير حلقته فقط إن مُنح).
 */
class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.generate') || $user->can('reports.download');
    }

    public function view(User $user, Report $report): bool
    {
        if (! $this->withinTenant($user, $report)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        // المستخدم الذي طلب التقرير يمكنه رؤيته دوماً ضمن منظمته.
        if ($report->requested_by === $user->id) {
            return $user->can('reports.download');
        }

        return $user->can('reports.download') && ($user->hasRole('supervisor') || $user->hasRole('teacher'));
    }

    public function generate(User $user): bool
    {
        return $user->can('reports.generate');
    }

    public function download(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    private function withinTenant(User $user, Report $report): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $report->organization_id;
    }
}
