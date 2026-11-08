<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * استعلام سجلات التدقيق (قراءة فقط) مع فلترة الحدث والمستخدم والتاريخ.
 * النطاق محصور تلقائياً بمنظمة المستخدم عبر BelongsToOrganization.
 */
class AuditLogQuery
{
    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    public function paginate(
        ?string $event = null,
        ?int $userId = null,
        ?string $from = null,
        ?string $to = null,
        ?int $perPage = null,
        bool $withQueryString = false,
    ): LengthAwarePaginator {
        $auditLogs = AuditLog::query()
            ->with('user')
            ->when($event !== null && $event !== '', fn ($query) => $query->where('event', $event))
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->when($from !== null && $from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null && $to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')
            ->paginate($perPage ?? (int) config('tahfeez.per_page'));

        return $withQueryString ? $auditLogs->withQueryString() : $auditLogs;
    }
}
