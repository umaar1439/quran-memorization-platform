<?php

namespace App\Policies;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('exams.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        if (! $this->withinTenant($user, $exam)) {
            return false;
        }

        return $user->can('exams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('exams.create');
    }

    public function update(User $user, Exam $exam): bool
    {
        if (! $this->withinTenant($user, $exam)) {
            return false;
        }

        return $user->can('exams.create') && $exam->status !== ExamStatus::Approved;
    }

    public function approve(User $user, Exam $exam): bool
    {
        if (! $this->withinTenant($user, $exam)) {
            return false;
        }

        return $user->can('exams.approve');
    }

    private function withinTenant(User $user, Exam $exam): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id !== null
            && $user->organization_id === $exam->organization_id;
    }
}
