<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PaginatedResponse, Certificate } from '@/Types';

const props = defineProps<{
    certificates: PaginatedResponse<Certificate>;
}>();

const statusLabels: Record<string, string> = {
    issued: 'مُصدرة',
    revoked: 'ملغاة',
};

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function onPageChange(page: number): void {
    router.get(route('student.certificates'), { page }, { preserveState: true });
}
</script>

<template>
    <Head title="شهاداتي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">شهاداتي</h2>
        </template>

        <div class="mx-auto max-w-4xl space-y-4">
            <EmptyState
                v-if="certificates.data.length === 0"
                title="لا توجد شهادات بعد"
                description="ستظهر شهاداتك هنا بعد اعتماد إنجازك."
            />

            <div
                v-for="certificate in certificates.data"
                :key="certificate.id"
                class="rounded-lg bg-white shadow-sm"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ certificate.certificate_type }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            أُصدرت في {{ formatDate(certificate.issued_at) }}
                        </p>
                    </div>
                    <StatusBadge :status="certificate.status" :labels="statusLabels" />
                </div>
                <div class="px-4 py-3">
                    <p class="text-xs text-gray-500">رمز التحقق</p>
                    <p class="font-mono text-sm font-medium text-gray-800">
                        {{ certificate.verification_code }}
                    </p>
                </div>
            </div>

            <!-- ترقيم صفحات -->
            <div
                v-if="certificates.last_page > 1"
                class="flex items-center justify-between rounded-lg bg-white px-4 py-3 shadow-sm"
            >
                <span class="text-sm text-gray-500">
                    صفحة {{ certificates.current_page }} من {{ certificates.last_page }}
                </span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        :disabled="certificates.current_page <= 1"
                        class="min-h-[44px] rounded-md border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-40"
                        @click="onPageChange(certificates.current_page - 1)"
                    >
                        السابق
                    </button>
                    <button
                        type="button"
                        :disabled="certificates.current_page >= certificates.last_page"
                        class="min-h-[44px] rounded-md border border-gray-300 px-3 py-1.5 text-sm disabled:opacity-40"
                        @click="onPageChange(certificates.current_page + 1)"
                    >
                        التالي
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
