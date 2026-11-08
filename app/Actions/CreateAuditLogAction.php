<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Action مشترك لإنشاء سجلات audit_logs (§1، §5: Append-Only، لا Update
 * ولا Delete إطلاقاً). يُضيف تلقائياً user_id/organization_id/ip_address/
 * user_agent/request_id، ويحظر تسرّب بيانات حساسة (passwords/tokens)
 * عبر redaction على old_values/new_values (§4 تهديد #9 Mass Assignment
 * و #2 Broken Access Control).
 */
class CreateAuditLogAction
{
    /**
     * مفاتيح حساسة تُحذف من old_values/new_values قبل التخزين، بصرف
     * النظر عن مصدرها (منع تسرّب أسرار داخل سجل تدقيق يُقرأ لاحقاً).
     */
    private const REDACTED_KEYS = [
        'password', 'password_confirmation', 'token', 'remember_token',
        'api_token', 'secret', 'access_token', 'refresh_token',
    ];

    public function execute(
        int $organizationId,
        string $event,
        string $auditableType,
        int|string $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $this->resolveRequestId(),
        ]);
    }

    /**
     * يقرأ request_id من app container (يُعيَّن عبر SetRequestId middleware
     * لكل HTTP request — §Phase 14 نقطة 1، 2). إن لم يكن متاحاً (مثال:
     * Job/Command خارج سياق HTTP)، يُولَّد UUID مستقل كحل بديل.
     */
    private function resolveRequestId(): string
    {
        if (app()->bound('request_id')) {
            return (string) app('request_id');
        }

        return (string) Str::uuid();
    }

    /**
     * يحذف المفاتيح الحساسة (وأي مفتاح متداخل بنفس الاسم) من المصفوفة
     * قبل التخزين في audit_logs.
     */
    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $redacted = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $redacted[$key] = '[REDACTED]';

                continue;
            }

            $redacted[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $redacted;
    }
}
