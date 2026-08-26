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
            <h2 class="text-xl font-semibold leading-tight text-gray-800">حلقتي</h2>
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <EmptyState v-if="circles.length === 0" title="لا توجد حلقة مرتبطة بك حالياً" />

            <div v-for="circle in circles" :key="circle.id" class="rounded-lg bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ circle.name }}</h3>
                        <p class="text-sm text-gray-500">{{ circle.mosque?.name }}</p>
                    </div>
                    <StatusBadge :status="circle.status" />
                </div>

                <EmptyState
                    v-if="!circle.studentProfiles?.length"
                    title="لا يوجد طلاب مسجَّلون في هذه الحلقة"
                />

                <ul v-else class="divide-y divide-gray-100">
                    <li
                        v-for="student in circle.studentProfiles"
                        :key="student.id"
                        class="flex items-center justify-between px-4 py-3 text-sm"
                    >
                        <span class="font-medium text-gray-800">{{ student.user?.name }}</span>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="student.status" />
                            <Link :href="route('students.show', student.id)" class="font-medium text-teal-600 hover:underline">
                                عرض ملف الطالب
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
