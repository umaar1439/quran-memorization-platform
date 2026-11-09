<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Circle;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeatureFlag;
use App\Models\Mosque;
use App\Models\Recitation;
use App\Models\Report;
use App\Models\Setting;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\TeacherAttendance;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\CirclePolicy;
use App\Policies\ExamPolicy;
use App\Policies\ExamResultPolicy;
use App\Policies\FeatureFlagPolicy;
use App\Policies\MosquePolicy;
use App\Policies\RecitationPolicy;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\StudentGoalPolicy;
use App\Policies\StudentProfilePolicy;
use App\Policies\StudentTransferPolicy;
use App\Policies\TeacherAttendancePolicy;
use App\Policies\TeacherProfilePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

/**
 * تسجيل Policy لكل Model رئيسي (Phase 3 — التخويل وعزل المستأجرين).
 * انظر §2، §3 من وثيقة التحقق المعماري.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Mosque::class => MosquePolicy::class,
        Circle::class => CirclePolicy::class,
        StudentProfile::class => StudentProfilePolicy::class,
        TeacherProfile::class => TeacherProfilePolicy::class,
        Recitation::class => RecitationPolicy::class,
        TeacherAttendance::class => TeacherAttendancePolicy::class,
        Exam::class => ExamPolicy::class,
        ExamResult::class => ExamResultPolicy::class,
        Certificate::class => CertificatePolicy::class,
        Report::class => ReportPolicy::class,
        Setting::class => SettingPolicy::class,
        FeatureFlag::class => FeatureFlagPolicy::class,
        StudentTransfer::class => StudentTransferPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        StudentGoal::class => StudentGoalPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // بوابة لوحة التحكم الإدارية (أدوار الإدارة فقط) — Defense-in-Depth
        // فوق route middleware `role:...` (كل action يجب أن يفرض تفويضاً صريحاً).
        Gate::define('view-admin-dashboard', function (User $user) {
            return $user->hasRole('org-admin')
                || $user->hasRole('super-admin')
                || $user->hasRole('supervisor');
        });
    }
}
