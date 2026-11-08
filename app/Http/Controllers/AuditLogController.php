<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Queries\AuditLogQuery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * index فقط — قراءة (Read-Only) لسجلات التدقيق (org-admin فقط، Phase 14
 * نقطة 5). لا update ولا delete أبداً على AuditLog (Append-Only، §1، §5).
 * فلترة بالتاريخ والحدث والمستخدم. النطاق مقيَّد تلقائياً بمنظمة المستخدم
 * (Global Scope عبر BelongsToOrganization)، وSuper Admin يرى كل المنظمات.
 */
class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQuery $auditLogs,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        return AuditLogResource::collection($this->auditLogs->paginate(
            $request->string('event')->toString() ?: null,
            $request->filled('user_id') ? $request->integer('user_id') : null,
            $request->date('from')?->toDateString(),
            $request->date('to')?->toDateString(),
        ));
    }

    /**
     * صفحة سجل التدقيق (Inertia) — نفس الفلاتر المتاحة في index أعلاه.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        return Inertia::render('Admin/AuditLogs/Index', [
            'auditLogs' => $this->auditLogs->paginate(
                $request->string('event')->toString() ?: null,
                $request->filled('user_id') ? $request->integer('user_id') : null,
                $request->date('from')?->toDateString(),
                $request->date('to')?->toDateString(),
                (int) config('tahfeez.per_page_large'),
                withQueryString: true,
            ),
            'filters' => $request->only('event', 'user_id', 'from', 'to'),
        ]);
    }
}
