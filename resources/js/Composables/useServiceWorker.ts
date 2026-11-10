/**
 * useServiceWorker — دورة حياة الـ Service Worker: تسجيل، كشف تحديث، وتطبيق مؤجَّل.
 *
 * القرار: لا نستخدم skipWaiting تلقائياً في install. تفعيل SW جديد وسط الجلسة
 * بينما الصفحة الحالية تطلب أصولاً hashed عُدِّلت بالكامل على الخادم = شاشة بيضاء
 * (الأصول القديمة لم تعد موجودة). لذلك ينتظر الـ SW الجديد حتى يطلب المستخدم
 * التحديث من PwaUpdateBanner.
 */

import { readonly, ref } from 'vue';

const updateReady = ref(false);
const isApplying = ref(false);

let waitingWorker: ServiceWorker | null = null;
let reloadRequested = false;

function markUpdateReady(worker: ServiceWorker): void {
    waitingWorker = worker;
    updateReady.value = true;
}

async function register(): Promise<void> {
    try {
        const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

        // نسخة جديدة جاهزة من جلسة سابقة (الصفحة أُعيد تحميلها والـ SW ينتظر).
        if (registration.waiting && navigator.serviceWorker.controller) {
            markUpdateReady(registration.waiting);
        }

        registration.addEventListener('updatefound', () => {
            const installing = registration.installing;

            if (!installing) {
                return;
            }

            installing.addEventListener('statechange', () => {
                // controller موجود = هذه ليست أول زيارة، بل تحديث فعلي.
                if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                    markUpdateReady(installing);
                }
            });
        });

        // فحص دوري عند العودة إلى التطبيق (تبويب كان في الخلفية).
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                void registration.update();
            }
        });
    } catch (error) {
        // فشل التسجيل (سياق غير آمن، 404، سياسة متصفح) لا يجب أن يوقف التطبيق.
        console.warn('[pwa] تعذّر تسجيل Service Worker', error);
    }
}

export function registerServiceWorker(): void {
    if (!('serviceWorker' in navigator) || import.meta.env.DEV) {
        return;
    }

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        // لا نُعيد التحميل إلا بعد طلب صريح، حتى لا تُفقد أي بيانات مُدخلة.
        if (reloadRequested) {
            window.location.reload();
        }
    });

    window.addEventListener('load', () => {
        void register();
    });
}

export function applyUpdate(): void {
    if (!waitingWorker) {
        return;
    }

    isApplying.value = true;
    reloadRequested = true;
    waitingWorker.postMessage({ type: 'SKIP_WAITING' });

    // حماية من تعليق الحالة إن لم يُفعّل الـ SW الجديد (متصفح علّق التبديل).
    window.setTimeout(() => {
        if (isApplying.value) {
            isApplying.value = false;
            console.warn('[pwa] لم يكتمل تفعيل الإصدار الجديد؛ أعد تحميل الصفحة يدوياً.');
        }
    }, 8000);
}

export function useServiceWorkerUpdate() {
    return {
        updateReady: readonly(updateReady),
        isApplying: readonly(isApplying),
        applyUpdate,
    };
}
