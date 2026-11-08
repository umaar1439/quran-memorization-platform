<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Circle;
use Illuminate\Database\Eloquent\Collection;

/**
 * خيارات القوائم المنسدلة للحلقات (محدودة العدد والأعمدة) لتُستخدم في نماذج
 * الإدارة دون تحميل جدول غير محدود.
 */
class CircleOptionsQuery
{
    /**
     * @return Collection<int, Circle>
     */
    public function all(): Collection
    {
        return Circle::query()
            ->with('mosque:id,name')
            ->orderBy('name')
            ->limit((int) config('tahfeez.filter_options_limit'))
            ->get(['id', 'name', 'mosque_id']);
    }
}
