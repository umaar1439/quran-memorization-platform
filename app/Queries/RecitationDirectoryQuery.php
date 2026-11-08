<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Recitation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام قائمة التسميعات بحسب دور المستخدم: المعلم يرى تسميعاته،
 * الطالب يرى تسميعاته، وولي الأمر يرى تسميعات أبنائه فقط.
 */
class RecitationDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Recitation>
     */
    public function paginate(User $user): LengthAwarePaginator
    {
        $query = Recitation::query()->with('errors');

        // if/elseif (لا when متسلسلة) حتى يبقى النطاق واحداً فقط لكل مستخدم
        // كما في السلوك الأصلي؛ الأدوار العليا لا تُقيَّد هنا بل عبر Global Scope.
        if ($user->hasRole('teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('student')) {
            $query->where('student_id', $user->studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $guardian = $user->guardian;

            $query->whereIn(
                'student_id',
                $guardian ? $guardian->students()->pluck('student_profiles.id') : collect(),
            );
        }

        return $query
            ->latest('performed_at')
            ->paginate((int) config('tahfeez.per_page'));
    }
}
