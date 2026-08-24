/**
 * useNetworkStatus — Composable يراقب navigator.onLine وأحداث online/offline
 * ويُرجع reactive ref لحالة الاتصال (Phase 12 نقطة 4).
 */

import { onMounted, onUnmounted, ref, type Ref } from 'vue';

export function useNetworkStatus(): { isOnline: Ref<boolean> } {
    const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);

    function handleOnline(): void {
        isOnline.value = true;
    }

    function handleOffline(): void {
        isOnline.value = false;
    }

    onMounted(() => {
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    });

    onUnmounted(() => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    });

    return { isOnline };
}
