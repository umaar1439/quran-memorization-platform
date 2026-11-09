import type { PaginatedResponse } from '@/Types';

/**
 * عقد وحدة الشهادات (Certificates) — صفحة Student/Certificates.
 *
 * القائمة تُرسَل كـ Eloquent paginator (CertificateDirectoryQuery::paginate)،
 * و`certificates.store` يُرجع CertificateResource (202) بعد جدولة التوليد.
 */

/** يطابق App\Enums\CertificateStatus. */
export type CertificateStatusValue = 'pending' | 'issued' | 'failed' | 'revoked';

/** ما تُسلسِله Eloquent داخل certificates.data. */
export type CertificatePageModel = {
    /** ULID نصّي (لا integer). */
    id: string;
    organization_id: number;
    student_id: number;
    exam_id: number | null;
    certificate_type: string;
    issued_at: string;
    issued_by: number | null;
    verification_code: string;
    status: CertificateStatusValue;
    created_at: string | null;
};

export type CertificatesPageProps = {
    certificates: PaginatedResponse<CertificatePageModel>;
};

/** حمولة إصدار شهادة — نفس مفاتيح StoreCertificateRequest. */
export type CertificateFormPayload = {
    student_id: string | number;
    exam_id?: string | number;
    certificate_type: string;
};
