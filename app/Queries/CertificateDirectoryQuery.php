<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام الشهادات بحسب نطاق المستخدم: الطالب يرى شهاداته، وولي الأمر
 * يرى شهادات أبنائه، والإدارة ترى نطاق منظمتها عبر Global Scope.
 */
class CertificateDirectoryQuery
{
    /**
     * @return LengthAwarePaginator<int, Certificate>
     */
    public function paginate(User $user, bool $withQueryString = false): LengthAwarePaginator
    {
        $query = Certificate::query();

        if ($user->hasRole('student')) {
            $query->where('student_id', $user->studentProfile?->id);
        } elseif ($user->hasRole('parent')) {
            $query->whereIn(
                'student_id',
                $user->guardian
                    ? $user->guardian->students()->pluck('student_profiles.id')
                    : collect(),
            );
        }

        $certificates = $query
            ->latest('issued_at')
            ->paginate((int) config('tahfeez.per_page'));

        return $withQueryString ? $certificates->withQueryString() : $certificates;
    }

    /**
     * بحث عام (بلا نطاق منظمة) عن شهادة صادرة بكود التحقق — بيانات محدودة فقط.
     */
    public function findIssuedByVerificationCode(string $code): ?Certificate
    {
        return Certificate::withoutGlobalScopes()
            ->where('verification_code', $code)
            ->where('status', CertificateStatus::Issued)
            ->first();
    }
}
