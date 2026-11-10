/**
 * useInstallPrompt — التقاط حدث beforeinstallprompt وعرض دعوة تثبيت واحدة.
 *
 * beforeinstallprompt غير معرّف في lib.dom لهذا نعرّف شكله هنا. الحدث يُطلق مرة
 * واحدة في عمر الصفحة؛ نمنع سلوكه الافتراضي ونحتفظ به لاستخدامه عند ضغط المستخدم.
 */

import { readonly, ref } from 'vue';

interface BeforeInstallPromptEvent extends Event {
    readonly userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>;
    prompt(): Promise<void>;
}

const canInstall = ref(false);

let deferredPrompt: BeforeInstallPromptEvent | null = null;

export function initInstallPrompt(): void {
    if (typeof window === 'undefined' || !('onbeforeinstallprompt' in window)) {
        return;
    }

    // مثبّت مسبقاً: لا نعرض دعوة التثبيت.
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event as BeforeInstallPromptEvent;
        canInstall.value = true;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        canInstall.value = false;
    });
}

export async function promptInstall(): Promise<'accepted' | 'dismissed' | 'unavailable'> {
    if (!deferredPrompt) {
        return 'unavailable';
    }

    await deferredPrompt.prompt();

    const { outcome } = await deferredPrompt.userChoice;

    // الحدث صالح لاستخدام واحد فقط؛ المتصفح يطلقه مجدداً عند الحاجة.
    deferredPrompt = null;
    canInstall.value = false;

    return outcome;
}

export function useInstallPrompt() {
    return { canInstall: readonly(canInstall), promptInstall };
}
