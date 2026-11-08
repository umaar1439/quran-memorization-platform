<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Circle } from '@/Types';

defineProps<{
    circles: Circle[];
}>();
</script>

<template>
    <Head title="حلقتي" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg font-semibold leading-tight text-content-strong sm:text-xl">حلقتي</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <EmptyState v-if="circles.length === 0" title="لا توجد حلقة مرتبطة بك حالياً" />

            <div v-for="circle in circles" :key="circle.id" class="card">
                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-edge px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-content">{{ circle.name }}</h3>
                        <p class="text-sm text-content-muted">{{ circle.mosque?.name }}</p>
                    </div>
                    <StatusBadge :status="circle.status" />
                </div>

                <EmptyState
                    v-if="!circle.studentProfiles?.length"
                    title="لا يوجد طلاب مسجَّلون في هذه الحلقة"
                />

                <ul v-else class="divide-y divide-edge-soft">
                    <li
                        v-for="student in circle.studentProfiles"
                        :key="student.id"
                        class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-4 py-3 text-sm"
                    >
                        <span class="font-medium text-content-strong">{{ student.user?.name }}</span>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="student.status" />
                            <Link :href="route('students.show', student.id)" class="link">
                                عرض ملف الطالب
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
