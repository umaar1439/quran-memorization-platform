<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\FeatureFlag;
use App\Models\User;

/**
 * generate: org-admin فقط + feature flag check (certificates.enabled).
 */
class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certificates.generate') || $user->hasRole('student') || $user->hasRole('parent');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if (! $this->withinTenant($user, $certificate)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        if ($user->hasRole('student')) {
            return $certificate->student && $certificate->student->user_id === $user->id;
        }

        if ($user->hasRole('parent')) {
            $studentPolicy = new StudentProfilePolicy;

            return $certificate->student
                && $studentPolicy->guardianCanView($user, $certificate->student);
        }

        return false;
    }

    public function generate(User $user): bool
    {
        if (! $user->hasRole('org-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        if (! $user->can('certificates.generate')) {
            return false;
        }

        return $this->featureEnabled($user->organization_id);
    }

    private function featureEnabled(?int $organizationId): bool
    {
        return (bool) FeatureFlag::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('key', 'certificates.enabled')
            ->value('enabled');
    }

    private function withinTenant(User $user, Certificate $certificate): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $certificate->organization_id;
    }
}
