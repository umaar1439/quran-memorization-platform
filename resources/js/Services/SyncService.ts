/**
 * SyncService — يُزامن OfflineQueue مع الخادم عند عودة الاتصال
 * (Phase 12 نقطة 5). يتعامل مع أربع نتائج ممكنة لكل عملية:
 *   - success: الخادم أنشأ السجل فعلاً → synced.
 *   - duplicate (idempotent): idempotency_key موجود مسبقاً، الخادم يرجع
 *     نجاح صامت بنفس السجل (§4 تهديد #14 Duplicate Offline Sync) → synced.
 *   - conflict: تعارض (مثل 409/422 يشير لحالة غير متوقعة) → conflict.
 *   - auth error: 401/403 → failed (لا يُعاد المحاولة تلقائياً دون تدخل المستخدم).
 *
 * المزامنة تتم بالترتيب (created_at) — لا تزامن متوازٍ للعمليات المعلقة.
 */

import axios, { type AxiosError } from 'axios';
import { OfflineQueue, type OfflineOperation } from './OfflineQueue';

/**
 * خريطة اسم العملية → مسار API. يمكن التوسّع لاحقاً بعمليات إضافية
 * (تحديث حضور، أهداف...) دون تغيير هيكلي في SyncService نفسه.
 */
const OPERATION_ENDPOINTS: Record<string, string> = {
    'recitations.create': '/recitations',
};

export type SyncOutcome = 'success' | 'duplicate' | 'conflict' | 'auth_error' | 'network_error';

export interface SyncResult {
    id: string;
    outcome: SyncOutcome;
}

export const SyncService = {
    /**
     * يُزامن كل العمليات المعلقة بالترتيب. يُوقف المزامنة عند أول
     * network_error (لا فائدة من محاولة الباقي إن كان الاتصال انقطع فعلاً)،
     * لكن يستمر عند auth_error/conflict للعمليات التالية.
     */
    async syncAll(): Promise<SyncResult[]> {
        const pending = await OfflineQueue.getPending();
        const results: SyncResult[] = [];

        for (const entry of pending) {
            const result = await this.syncOne(entry);
            results.push(result);

            if (result.outcome === 'network_error') {
                break;
            }
        }

        return results;
    },

    async syncOne(entry: OfflineOperation): Promise<SyncResult> {
        const endpoint = OPERATION_ENDPOINTS[entry.operation];

        if (!endpoint) {
            await OfflineQueue.updateStatus(entry.id, 'failed');

            return { id: entry.id, outcome: 'conflict' };
        }

        await OfflineQueue.updateStatus(entry.id, 'syncing');

        try {
            await axios.post(endpoint, {
                ...entry.payload,
                idempotency_key: entry.idempotency_key,
            });

            await OfflineQueue.updateStatus(entry.id, 'synced');

            return { id: entry.id, outcome: 'success' };
        } catch (error) {
            return this.handleSyncError(entry, error as AxiosError);
        }
    },

    async handleSyncError(entry: OfflineOperation, error: AxiosError): Promise<SyncResult> {
        const status = error.response?.status;

        // 401/403: خطأ مصادقة/تفويض — لا يُعاد المحاولة تلقائياً.
        if (status === 401 || status === 403) {
            await OfflineQueue.updateStatus(entry.id, 'failed');

            return { id: entry.id, outcome: 'auth_error' };
        }

        // 409: تعارض صريح من الخادم.
        if (status === 409) {
            await OfflineQueue.updateStatus(entry.id, 'conflict');

            return { id: entry.id, outcome: 'conflict' };
        }

        // 422 مع نفس idempotency_key يُعامَل كنجاح صامت (idempotent) إن كان
        // سبب الرفض هو تكرار المفتاح، وإلا يُعامَل كتعارض تحقق (validation).
        if (status === 422) {
            const errors = (error.response?.data as { errors?: Record<string, string[]> })?.errors;
            const isDuplicateKey = Boolean(errors?.idempotency_key);

            if (isDuplicateKey) {
                await OfflineQueue.updateStatus(entry.id, 'synced');

                return { id: entry.id, outcome: 'duplicate' };
            }

            await OfflineQueue.updateStatus(entry.id, 'conflict');

            return { id: entry.id, outcome: 'conflict' };
        }

        // لا استجابة من الخادم إطلاقاً → انقطاع اتصال فعلي.
        if (!error.response) {
            await OfflineQueue.incrementRetry(entry.id);
            await OfflineQueue.updateStatus(entry.id, 'pending');

            return { id: entry.id, outcome: 'network_error' };
        }

        await OfflineQueue.incrementRetry(entry.id);
        await OfflineQueue.updateStatus(entry.id, 'failed');

        return { id: entry.id, outcome: 'conflict' };
    },
};
