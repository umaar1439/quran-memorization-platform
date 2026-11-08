/**
 * useTheme — مصدر واحد لحالة الثيم (نهاري/ليلي) في التطبيق كله.
 *
 * القرار: الوضع الليلي اختياري بالكامل ولا يتبع إعداد النظام، والافتراضي نهاري.
 * الاختيار يُحفظ في متصفح المستخدم (localStorage) فيبقى بعد إعادة التحميل،
 * ويُطبَّق كالصنف `dark` على <html> ليعمل مع Tailwind (darkMode: 'class')
 * ومتغيّرات الألوان في app.css.
 *
 * الحالة على مستوى الوحدة (module) لا داخل المكوّن، حتى يتشاركها كل من
 * يستهلك useTheme() (الشريط العلوي، صفحة الدخول، صفحة الترحيب) في تبديل واحد.
 */
import { computed, readonly, ref } from 'vue';

export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'theme';

/** لون شريط المتصفح على الجوال لكل وضع (teal-700 / gray-900) */
const THEME_COLORS: Record<Theme, string> = {
    light: '#0f766e',
    dark: '#111827',
};

const theme = ref<Theme>('light');

/**
 * قراءة الاختيار المحفوظ. أي قيمة غير صحيحة (أو تعطيل التخزين في وضع
 * التصفح المتخفّي) تُعيد النهاري، فلا ينكسر التطبيق أبداً بسبب الثيم.
 */
function storedTheme(): Theme {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === 'dark' ? 'dark' : 'light';
    } catch {
        return 'light';
    }
}

/** تطبيق الثيم على المستند (صنف + color-scheme + لون شريط المتصفح) */
function paintTheme(value: Theme): void {
    const root = document.documentElement;
    const isDark = value === 'dark';

    root.classList.toggle('dark', isDark);
    root.style.colorScheme = value;

    document.head
        .querySelector<HTMLMetaElement>('meta[name="theme-color"]')
        ?.setAttribute('content', THEME_COLORS[value]);
}

/**
 * يُستدعى مرة واحدة عند إقلاع التطبيق (app.ts) لمزامنة حالة Vue مع ما
 * طبّقه السكربت المضمّن في <head> قبل أول رسم (منع وميض الثيم).
 */
export function initTheme(): void {
    theme.value = storedTheme();
    paintTheme(theme.value);

    // مزامنة التبويبات المفتوحة: تغيير الوضع في تبويب يُطبَّق على البقية فوراً.
    window.addEventListener('storage', (event: StorageEvent) => {
        if (event.key !== STORAGE_KEY) {
            return;
        }

        theme.value = storedTheme();
        paintTheme(theme.value);
    });
}

export function setTheme(value: Theme): void {
    theme.value = value;
    paintTheme(value);

    try {
        window.localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // التخزين معطّل: يبقى التبديل صالحاً لهذه الجلسة فقط.
    }
}

export function toggleTheme(): void {
    setTheme(theme.value === 'dark' ? 'light' : 'dark');
}

export function useTheme() {
    return {
        theme: readonly(theme),
        isDark: computed(() => theme.value === 'dark'),
        setTheme,
        toggleTheme,
    };
}
