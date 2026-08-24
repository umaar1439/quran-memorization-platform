/**
 * OfflineQueue — يُخزّن العمليات المعلقة (offline) في IndexedDB عبر `idb`
 * (Phase 12 نقطة 3). كل عملية تحمل idempotency_key يُولَّد على العميل
 * (UUID) وقت الإدخال الفعلي، لا وقت المزامنة — يمنع تكرار الإدخال عند
 * إعادة المزامنة (انظر docs/architecture-validation.md §4 تهديد #14).
 */

import { openDB, type IDBPDatabase } from 'idb';

export type OfflineOperationStatus = 'pending' | 'syncing' | 'synced' | 'failed' | 'conflict';

export interface OfflineOperation {
    id: string;
    operation: string;
    payload: Record<string, unknown>;
    idempotency_key: string;
    created_at: string;
    retry_count: number;
    status: OfflineOperationStatus;
}

const DB_NAME = 'tahfeez-offline';
const DB_VERSION = 1;
const STORE_NAME = 'offline_queue';

let dbPromise: Promise<IDBPDatabase> | null = null;

function getDb(): Promise<IDBPDatabase> {
    if (!dbPromise) {
        dbPromise = openDB(DB_NAME, DB_VERSION, {
            upgrade(db) {
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                    store.createIndex('status', 'status');
                    store.createIndex('created_at', 'created_at');
                }
            },
        });
    }

    return dbPromise;
}

/**
 * يُولّد idempotency_key فريداً على العميل وقت الإدخال الفعلي (لا وقت المزامنة).
 */
export function generateIdempotencyKey(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    // fallback بسيط لبيئات لا تدعم crypto.randomUUID.
    return `${Date.now()}-${Math.random().toString(16).slice(2)}-${Math.random().toString(16).slice(2)}`;
}

export const OfflineQueue = {
    /**
     * يُضيف عملية جديدة إلى الطابور بحالة "pending" مع idempotency_key
     * مولَّد على العميل الآن (وقت الإدخال الفعلي).
     */
    async enqueue(operation: string, payload: Record<string, unknown>): Promise<OfflineOperation> {
        const db = await getDb();

        const entry: OfflineOperation = {
            id: generateIdempotencyKey(),
            operation,
            payload,
            idempotency_key: generateIdempotencyKey(),
            created_at: new Date().toISOString(),
            retry_count: 0,
            status: 'pending',
        };

        await db.put(STORE_NAME, entry);

        return entry;
    },

    /**
     * يُرجع كل العمليات المعلقة (pending/failed) مُرتَّبة بحسب created_at
     * (الترتيب مهم عند المزامنة — §Phase 12 نقطة 3).
     */
    async getPending(): Promise<OfflineOperation[]> {
        const db = await getDb();
        const all = await db.getAll(STORE_NAME) as OfflineOperation[];

        return all
            .filter((entry) => entry.status === 'pending' || entry.status === 'failed')
            .sort((a, b) => a.created_at.localeCompare(b.created_at));
    },

    async getAll(): Promise<OfflineOperation[]> {
        const db = await getDb();

        return db.getAll(STORE_NAME) as Promise<OfflineOperation[]>;
    },

    async get(id: string): Promise<OfflineOperation | undefined> {
        const db = await getDb();

        return db.get(STORE_NAME, id) as Promise<OfflineOperation | undefined>;
    },

    async updateStatus(id: string, status: OfflineOperationStatus): Promise<void> {
        const db = await getDb();
        const entry = await db.get(STORE_NAME, id) as OfflineOperation | undefined;

        if (!entry) {
            return;
        }

        entry.status = status;
        await db.put(STORE_NAME, entry);
    },

    async incrementRetry(id: string): Promise<void> {
        const db = await getDb();
        const entry = await db.get(STORE_NAME, id) as OfflineOperation | undefined;

        if (!entry) {
            return;
        }

        entry.retry_count += 1;
        await db.put(STORE_NAME, entry);
    },

    async remove(id: string): Promise<void> {
        const db = await getDb();
        await db.delete(STORE_NAME, id);
    },

    async clear(): Promise<void> {
        const db = await getDb();
        await db.clear(STORE_NAME);
    },
};
