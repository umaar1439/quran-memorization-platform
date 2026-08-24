<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = $this->filteredQuery($request);

        return response()->json([
            'data' => $query->latest('created_at')->paginate(20),
        ]);
    }

    /**
     * صفحة سجل التدقيق (Inertia) — نفس الفلاتر المتاحة في index أعلاه.
     */
    public function page(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = $this->filteredQuery($request);

        return Inertia::render('Admin/AuditLogs/Index', [
            'auditLogs' => $query->latest('created_at')->paginate(30)->withQueryString(),
            'filters' => $request->only('event', 'user_id', 'from', 'to'),
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $query = AuditLog::query()->with('user');

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return $query;
    }
}
