import { router } from '@inertiajs/vue3';

/**
 * طبقة Model/API لسجل نشاط المعلم: تنقّل Inertia فقط (قراءة بحتة).
 *
 * لا يوجد ViewModel هنا: الصفحة بلا حالة محلية (لا نموذج ولا فلاتر)، وكل
 * ما تحتاجه هو مسار الترقيم — فإضافة Composable فارغ ستكون طبقة بلا وظيفة.
 */
export const TeacherActivityApi = {
    visit(page: number): void {
        router.get(route('teacher.activity.page'), { page }, { preserveState: true });
    },
};
