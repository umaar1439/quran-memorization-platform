import { router } from '@inertiajs/vue3';
import type { AttendanceBulkPayload, AttendanceFilters } from '@/Types/api/attendance';

/**
 * طبقة Model/API لوحدة الحضور: المكان الوحيد الذي يعرف مسارات attendance.*
 * وشكل الحمولة. الـ ViewModel والصفحات لا تستدعي router مباشرة.
 *
 * مسارات الكتابة تُرجع RedirectResponse (Inertia)، فتُغلَّف في Promise:
 * resolve عند onSuccess وreject بخريطة أخطاء التحقق عند onError، فيتعامل
 * الـ ViewModel مع النجاح والفشل بـ try/catch واحد (نمط StudentApi).
 */
export const AttendanceApi = {
    /** يحفظ ورقة الحضور اليومية لحلقة واحدة (upsert على الخادم). */
    saveSheet(payload: AttendanceBulkPayload): Promise<void> {
        return new Promise((resolve, reject) => {
            router.post(route('attendance.bulk'), payload, {
                preserveScroll: true,
                onSuccess: () => resolve(),
                onError: (errors) => reject(errors),
            });
        });
    },

    /**
     * تنقّل Inertia لإعادة تحميل الورقة بتاريخ/حلقة مختلفين. قراءة فقط:
     * يبني رابطاً بالمعاملات المطلوبة ويحذف القيم الفارغة.
     */
    visitSheet(query: AttendanceFilters, routeName = 'teacher.attendance.page'): void {
        router.get(route(routeName), omitEmpty(query), { preserveState: true });
    },
};

/** يحذف القيم الفارغة حتى لا تظهر `?status=` بلا معنى في الرابط. */
function omitEmpty(query: AttendanceFilters): AttendanceFilters {
    return Object.fromEntries(
        Object.entries(query).filter(
            ([, value]) => value !== undefined && value !== null && value !== '',
        ),
    ) as AttendanceFilters;
}
