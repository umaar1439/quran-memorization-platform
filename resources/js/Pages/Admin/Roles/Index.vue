<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import PageHeader from '@/Components/PageHeader.vue';
import SectionCard from '@/Components/SectionCard.vue';
import { useRolePermissions } from '@/Composables/useRolePermissions';
import type { RoleIndexPageProps } from '@/Types/api/user';

/**
 * العرض فقط: الدور المختار، مسودة الصلاحيات، تجميعها للعرض، والحفظ كلها في
 * useRolePermissions، وكل نداء شبكة في RoleApi.
 */
const props = defineProps<RoleIndexPageProps>();

const {
    selectedRoleId,
    selectedRole,
    canEdit,
    groups,
    selectedCount,
    errors,
    processing,
    roleLabel,
    isChecked,
    toggle,
    isGroupChecked,
    groupCheckedCount,
    toggleGroup,
    submit,
} = useRolePermissions(props);
</script>

<template>
    <Head title="الأدوار والصلاحيات" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader
                title="الأدوار والصلاحيات"
                description="حدّد ما يستطيع كل دور فعله. التغييرات تُطبَّق فوراً على الخادم وتُسجَّل في سجل التدقيق."
            />
        </template>

        <div class="mx-auto max-w-5xl space-y-5">
            <EmptyState v-if="roles.length === 0" title="لا توجد أدوار مُعرَّفة لهذه المنظمة" />

            <template v-else>
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="اختيار الدور">
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        type="button"
                        role="tab"
                        :aria-selected="role.id === selectedRoleId"
                        class="focus-ring inline-flex min-h-11 items-center gap-2 rounded-lg border px-3.5 text-sm font-semibold transition duration-150 ease-standard"
                        :class="
                            role.id === selectedRoleId
                                ? 'border-teal-700 bg-teal-700 text-white'
                                : 'border-edge bg-surface text-content-body hover:bg-surface-muted'
                        "
                        @click="selectedRoleId = role.id"
                    >
                        {{ roleLabel(role.name) }}
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                role.id === selectedRoleId
                                    ? 'bg-white/20 text-white'
                                    : 'bg-surface-sunken text-content-soft'
                            "
                        >
                            {{ role.permissions.length }}
                        </span>
                    </button>
                </div>

                <p
                    v-if="selectedRole && !selectedRole.editable"
                    class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    هذا الدور محمي من التعديل من الواجهة (طريق الإنقاذ عند الخطأ)، ويُضبط عبر الـ Seeder عند تجهيز المنظمة.
                </p>

                <form class="space-y-4" @submit.prevent="submit">
                    <SectionCard
                        v-for="group in groups"
                        :key="group.key"
                        :title="group.label"
                        :description="`${groupCheckedCount(group)} من ${group.permissions.length} صلاحية`"
                        :padded="false"
                    >
                        <template #actions>
                            <AppButton variant="secondary" size="sm" :disabled="!canEdit" @click="toggleGroup(group)">
                                {{ isGroupChecked(group) ? 'إلغاء تحديد الكل' : 'تحديد الكل' }}
                            </AppButton>
                        </template>

                        <ul class="grid gap-1 p-3 sm:grid-cols-2 sm:p-4">
                            <li v-for="permission in group.permissions" :key="permission.name">
                                <label
                                    class="flex min-h-11 items-center gap-2.5 rounded-md px-2 py-1.5 text-sm"
                                    :class="
                                        canEdit
                                            ? 'cursor-pointer text-content-body hover:bg-surface-muted'
                                            : 'cursor-not-allowed text-content-muted'
                                    "
                                >
                                    <Checkbox
                                        :checked="isChecked(permission.name)"
                                        :disabled="!canEdit"
                                        @update:checked="toggle(permission.name)"
                                    />
                                    <span class="min-w-0">
                                        <span class="block font-medium">{{ permission.label }}</span>
                                        <span class="block font-mono text-xs text-content-muted">
                                            {{ permission.name }}
                                        </span>
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </SectionCard>

                    <InputError :message="errors.permissions" />

                    <div class="card flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-5">
                        <p class="text-sm text-content-muted">{{ selectedCount }} صلاحية محددة</p>
                        <AppButton type="submit" :disabled="!canEdit" :loading="processing">
                            حفظ التغييرات
                        </AppButton>
                    </div>
                </form>
            </template>
        </div>
    </AuthenticatedLayout>
</template>
