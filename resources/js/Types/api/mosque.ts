import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة المساجد.
 *
 * مسارات admin.mosques.* تُرجع RedirectResponse (Inertia) لا JsonResource،
 * فلا يوجد wire contract بصيغة JSON؛ نُعرِّف نموذج الصف كما تُسلسِله Eloquent
 * فعلياً (MosqueDirectoryQuery::withCount('circles')) وحمولة النموذج كما تُرسَل.
 */

/** يطابق App\Enums\GenericStatus. */
export type MosqueStatusValue = 'active' | 'inactive';

/** ما تُسلسِله Eloquent داخل mosques.data. */
export type MosquePageModel = {
    id: number;
    organization_id: number;
    name: string;
    address: string | null;
    city: string | null;
    phone: string | null;
    status: MosqueStatusValue;
    /** عمود withCount('circles') في القائمة. */
    circles_count: number;
    created_at: string | null;
};

export type MosqueFilters = {
    search?: string;
};

export type MosqueIndexPageProps = {
    mosques: PaginatedResponse<MosquePageModel>;
    filters: MosqueFilters;
};

/**
 * حقول نموذج الإنشاء/التعديل — نفس مفاتيح StoreMosqueRequest.
 * الحقول الاختيارية نصوص فارغة (لا null) لأن FormInput يُصدر string،
 * والخادم يُطبِّع الفراغ إلى null داخل MosqueData.
 */
export type MosqueFormPayload = {
    name: string;
    address: string;
    city: string;
    phone: string;
    status: string;
};
