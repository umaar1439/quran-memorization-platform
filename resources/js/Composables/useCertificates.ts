import { CertificateApi } from '@/Services/CertificateApi';
import type { CertificatesPageProps } from '@/Types/api/certificate';

const STATUS_LABELS: Record<string, string> = {
    pending: 'قيد الإصدار',
    issued: 'مُصدرة',
    failed: 'فشل الإصدار',
    revoked: 'ملغاة',
};

/**
 * ViewModel لصفحة شهادات الطالب: تنسيق التواريخ والتنقّل بين الصفحات —
 * والمكوّن يعرض فقط.
 */
export function useCertificates(props: CertificatesPageProps) {
    function formatDate(value: string): string {
        return new Date(value).toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }

    function onPageChange(page: number): void {
        CertificateApi.visit(page);
    }

    return {
        certificates: props.certificates,
        statusLabels: STATUS_LABELS,
        formatDate,
        onPageChange,
    };
}
