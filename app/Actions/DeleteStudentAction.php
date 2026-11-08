<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

/**
 * حذف طالب: الملف الدراسي وحساب المستخدم في معاملة واحدة.
 */
class DeleteStudentAction
{
    public function execute(StudentProfile $student): void
    {
        DB::transaction(function () use ($student): void {
            $user = $student->user;

            $student->delete();

            $user?->delete();
        });
    }
}
