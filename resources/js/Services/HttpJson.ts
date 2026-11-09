import axios, { type AxiosError } from 'axios';
import type { ApiFailure, FormErrors } from '@/Types/api/common';

/**
 * غلاف موحَّد لنداءات JSON (axios) المستخدمة في الوحدات التي لا تُرجع
 * Inertia response (مثل reports.store وstudent-transfers.store).
 *
 * النجاح يعيد الحمولة فقط (`response.data`)، والفشل يرمي `ApiFailure`
 * بخريطة أخطاء تحقق (أول رسالة لكل حقل) ورسالة عامة — فيتعامل الـ ViewModel
 * مع كل الحالات بـ try/catch واحد بدل فحص أشكال AxiosError في كل صفحة.
 */

type LaravelErrorBody = {
    message?: string;
    errors?: Record<string, string[]>;
};

const FALLBACK_MESSAGE = 'حدث خطأ غير متوقع، حاول مرة أخرى.';

function toFailure(error: unknown): ApiFailure {
    if (!axios.isAxiosError(error)) {
        return { errors: {}, message: FALLBACK_MESSAGE };
    }

    const body = (error as AxiosError<LaravelErrorBody>).response?.data;
    const errors: FormErrors = {};

    Object.entries(body?.errors ?? {}).forEach(([field, messages]) => {
        const first = messages?.[0];

        if (first) {
            errors[field] = first;
        }
    });

    return { errors, message: body?.message ?? FALLBACK_MESSAGE };
}

export async function postJson<T>(url: string, payload: Record<string, unknown> = {}): Promise<T> {
    try {
        const response = await window.axios.post<T>(url, payload);

        return response.data;
    } catch (error) {
        throw toFailure(error);
    }
}

export async function patchJson<T>(url: string, payload: Record<string, unknown> = {}): Promise<T> {
    try {
        const response = await window.axios.patch<T>(url, payload);

        return response.data;
    } catch (error) {
        throw toFailure(error);
    }
}
