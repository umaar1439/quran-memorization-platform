<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ExamResultStatus;
use App\Models\ExamResult;
use App\Models\User;

/**
 * الثبات بعد الاعتماد: بعد status=approved، لا يجوز تعديل score/grade
 * مباشرة إطلاقاً على مستوى Policy — التصحيح فقط عبر Correction Workflow
 * منفصل (§5 دورة حياة البيانات).
 */
class ExamResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('exams.view') || $user->hasRole('student') || $user->hasRole('parent');
    }

    public function view(User $user, ExamResult $result): bool
    {
        if (! $this->withinTenant($user, $result)) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('supervisor')) {
            return $user->can('exams.view');
        }

        if ($user->hasRole('student')) {
            return $result->student && $result->student->user_id === $user->id;
        }

        if ($user->hasRole('parent')) {
            $studentPolicy = new StudentProfilePolicy;

            return $result->student
                && $studentPolicy->guardianCanView($user, $result->student, 'view_exam_results');
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('exams.create');
    }

    public function update(User $user, ExamResult $result): bool
    {
        if (! $this->withinTenant($user, $result)) {
            return false;
        }

        if ($result->status === ExamResultStatus::Approved) {
            return false;
        }

        return $user->can('exams.create');
    }

    public function approve(User $user, ExamResult $result): bool
    {
        if (! $this->withinTenant($user, $result)) {
            return false;
        }

        return $user->can('exams.approve');
    }

    private function withinTenant(User $user, ExamResult $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $result->organization_id;
    }
}
