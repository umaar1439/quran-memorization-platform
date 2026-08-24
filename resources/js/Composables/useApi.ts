/**
 * useApi — غلاف خفيف حول axios (المتاح عالمياً عبر resources/js/bootstrap.ts)
 * لاستدعاء Controllers الحالية التي تُرجع JSON صراحةً (وليس Inertia response)
 * مثل ExamController::store أو RecitationController::store. يُستخدم بدلاً
 * من useForm/router عندما يكون الـ endpoint المستهدف JSON-only (§ القرار 1).
 */

import axios, { type AxiosError } from 'axios';

export interface ApiErrorShape {
    message: string;
    errors?: Record<string, string[]>;
}

function extractErrorMessage(error: unknown): string {
    if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiErrorShape>;
        return axiosError.response?.data?.message ?? 'حدث خطأ غير متوقع، حاول مرة أخرى.';
    }

    return 'حدث خطأ غير متوقع، حاول مرة أخرى.';
}

function extractValidationErrors(error: unknown): Record<string, string[]> {
    if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiErrorShape>;
        return axiosError.response?.data?.errors ?? {};
    }

    return {};
}

export function useApi() {
    return {
        get: <T = unknown>(url: string, params?: Record<string, unknown>) =>
            window.axios.get<T>(url, { params }),
        post: <T = unknown>(url: string, data?: Record<string, unknown>) =>
            window.axios.post<T>(url, data),
        patch: <T = unknown>(url: string, data?: Record<string, unknown>) =>
            window.axios.patch<T>(url, data),
        delete: <T = unknown>(url: string) => window.axios.delete<T>(url),
        extractErrorMessage,
        extractValidationErrors,
    };
}
