<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Mosque;
use Illuminate\Database\Eloquent\Collection;

/**
 * خيارات القوائم المنسدلة للمساجد (محدودة العدد والأعمدة) لتُستخدم في نماذج
 * الإدارة دون تحميل جدول غير محدود في الذاكرة.
 */
class MosqueOptionsQuery
{
    /**
     * @return Collection<int, Mosque>
     */
    public function all(): Collection
    {
        return Mosque::query()
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'name']);
    }
}
