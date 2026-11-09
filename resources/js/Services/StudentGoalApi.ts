import { router } from '@inertiajs/vue3';

/**
 * طبقة Model/API لأهداف الطالب الحالي: تنقّل Inertia فقط.
 * الإنشاء/التعديل يحدثان من واجهات المعلم/الإدارة (JSON) وليست ضمن هذه
 * الصفحة، فلا نُضيف دوالاً غير مستخدمة هنا.
 */
export const StudentGoalApi = {
    visit(page: number): void {
        router.get(route('student.goals'), { page }, { preserveState: true });
    },
};
