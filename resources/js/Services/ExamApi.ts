import { useApi } from '@/Composables/useApi';
import type {
    ApiEnvelope,
    ExamCorrectionPayload,
    ExamCreatePayload,
    ExamResponse,
    ExamResultCreatePayload,
    ExamResultResponse,
} from '@/Types/api/exam';

/**
 * طبقة Model/API لوحدة الامتحانات والنتائج: المكان الوحيد الذي يعرف مسار كل
 * endpoint وشكل الطلب والاستجابة. الـ ViewModels والمكوّنات لا تستدعي axios.
 *
 * كل استدعاء يرمي استثناء axios عند فشل التحقق ليستخرجه المستدعي عبر
 * useApi().extractValidationErrors / extractErrorMessage (نفس سلوك ما قبل
 * إعادة الهيكلة).
 */
export const ExamApi = {
    /** يُنشئ امتحاناً (يبدأ draft دوماً) ويعيد المورد كما في ExamResource. */
    async create(payload: ExamCreatePayload): Promise<ExamResponse> {
        const api = useApi();
        const response = await api.post<ApiEnvelope<ExamResponse>>(route('exams.store'), payload);

        return response.data.data;
    },

    /** يسجّل نتيجة طالب (draft/submitted) ويعيد المورد كما في ExamResultResource. */
    async recordResult(payload: ExamResultCreatePayload): Promise<ExamResultResponse> {
        const api = useApi();
        const response = await api.post<ApiEnvelope<ExamResultResponse>>(
            route('exam-results.store'),
            payload,
        );

        return response.data.data;
    },

    /** يعتمد نتيجة (قفل نهائي + إشعار الطالب). إعادة الاعتماد لا تُغيّر شيئاً. */
    async approveResult(resultId: number): Promise<ExamResultResponse> {
        const api = useApi();
        const response = await api.post<ApiEnvelope<ExamResultResponse>>(
            route('exam-results.approve', resultId),
        );

        return response.data.data;
    },

    /** يصحّح نتيجة معتمدة عبر Correction Workflow (سبب إلزامي + audit_log). */
    async correctResult(
        resultId: number,
        payload: ExamCorrectionPayload,
    ): Promise<ExamResultResponse> {
        const api = useApi();
        const response = await api.post<ApiEnvelope<ExamResultResponse>>(
            route('exam-results.correct', resultId),
            payload,
        );

        return response.data.data;
    },
};
