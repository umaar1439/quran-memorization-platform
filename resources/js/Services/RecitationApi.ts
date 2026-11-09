import { useApi } from '@/Composables/useApi';
import type {
    ApiEnvelope,
    RecitationCreatePayload,
    RecitationResponse,
} from '@/Types/api/recitation';

/**
 * طبقة Model/API لوحدة التسميع: المكان الوحيد الذي يعرف مسار الـ endpoint
 * وشكل الطلب والاستجابة. المكوّنات والـ ViewModel لا تستدعي axios مباشرة.
 */
export const RecitationApi = {
    /**
     * يسجّل تسميعاً جديداً ويعيد المورد كما ورد في RecitationResource.
     * عند فشل التحقق يرمي استثناء axios ليستخرجه المستدعي (كما كان سابقاً).
     */
    async create(payload: RecitationCreatePayload): Promise<RecitationResponse> {
        const api = useApi();
        const response = await api.post<ApiEnvelope<RecitationResponse>>(
            route('recitations.store'),
            payload,
        );

        return response.data.data;
    },
};
