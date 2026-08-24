/**
 * useToast — Composable لإدارة إشعارات Toast عالمية (state مشترك خارج
 * أي مكوّن محدد) يعرضها مكوّن Toast.vue الوحيد في التطبيق.
 */

import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'info' | 'warning';

export interface ToastMessage {
    id: number;
    type: ToastType;
    message: string;
    duration: number;
}

const toasts = reactive<ToastMessage[]>([]);

let nextId = 1;

function dismiss(id: number): void {
    const index = toasts.findIndex((toast) => toast.id === id);

    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

function show(message: string, type: ToastType = 'info', duration = 4000): number {
    const id = nextId++;

    toasts.push({ id, type, message, duration });

    if (duration > 0) {
        setTimeout(() => dismiss(id), duration);
    }

    return id;
}

export function useToast() {
    return {
        toasts,
        show,
        dismiss,
        success: (message: string, duration?: number) => show(message, 'success', duration),
        error: (message: string, duration?: number) => show(message, 'error', duration),
        info: (message: string, duration?: number) => show(message, 'info', duration),
        warning: (message: string, duration?: number) => show(message, 'warning', duration),
    };
}
