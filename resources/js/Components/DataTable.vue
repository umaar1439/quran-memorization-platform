<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import AppButton from '@/Components/AppButton.vue';
import EmptyState from '@/Components/EmptyState.vue';

export interface DataTableColumn {
    key: string;
    label: string;
    sortable?: boolean;
}

/**
 * DataTable — عرض واحد لبيانات مجدولة، متكيّف مع حجم الشاشة:
 * - الحاسب (md+): جدول حقيقي برؤوس أعمدة قابلة للفرز.
 * - الجوال: قائمة بطاقات لكل سجل، فلا يوجد تمرير أفقي ولا عمود مقتطع.
 * العمود الأول هو عنوان البطاقة، والعمود المسمّى actions يُعرض كإجراءات أسفلها.
 * نفس خانات cell-* تُستخدم في العرضين حتى لا تُكرَّر الأعمدة المخصّصة في الصفحات.
 */
const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: any[];
        currentPage?: number;
        lastPage?: number;
        total?: number;
        searchable?: boolean;
        /** وصف يُقرأ لقارئ الشاشة: ما الذي يعرضه هذا الجدول */
        label?: string;
    }>(),
    {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        searchable: true,
        label: 'جدول البيانات',
    },
);

const emit = defineEmits<{
    'update:page': [page: number];
    sort: [key: string];
    search: [value: string];
}>();

const primaryColumn = computed<DataTableColumn | undefined>(() => props.columns[0]);
const actionColumn = computed<DataTableColumn | undefined>(() =>
    props.columns.find((column) => column.key === 'actions'),
);
const detailColumns = computed<DataTableColumn[]>(() =>
    props.columns.filter((column) => column.key !== primaryColumn.value?.key && column.key !== 'actions'),
);

function goToPage(page: number): void {
    if (page >= 1 && page <= props.lastPage) {
        emit('update:page', page);
    }
}
</script>

<template>
    <div class="card overflow-hidden">
        <div v-if="searchable" class="border-b border-edge p-3 sm:p-4">
            <label class="relative block max-w-sm">
                <span class="sr-only">بحث في الجدول</span>
                <AppIcon
                    name="search"
                    class="pointer-events-none absolute inset-y-0 start-3 my-auto text-content-muted"
                />
                <input
                    type="search"
                    autocomplete="off"
                    placeholder="بحث..."
                    class="field min-h-11 ps-10"
                    @input="emit('search', ($event.target as HTMLInputElement).value)"
                />
            </label>
        </div>

        <!-- لا توجد بيانات -->
        <EmptyState
            v-if="rows.length === 0"
            title="لا توجد بيانات لعرضها"
            description="لم يُسجَّل أي عنصر بعد، أو أن البحث الحالي لا يطابق شيئاً."
        />

        <!-- الجوال: بطاقات (بلا تمرير أفقي) -->
        <ul v-else class="divide-y divide-edge-soft md:hidden" :aria-label="label">
            <li v-for="(row, index) in rows" :key="(row.id as string | number) ?? index" class="min-w-0 p-4">
                <div class="min-w-0 break-words text-sm font-semibold text-content-strong">
                    <slot v-if="primaryColumn" :name="`cell-${primaryColumn.key}`" :row="row">
                        {{ row[primaryColumn.key] }}
                    </slot>
                </div>

                <dl v-if="detailColumns.length > 0" class="mt-2 space-y-1.5">
                    <div
                        v-for="column in detailColumns"
                        :key="column.key"
                        class="flex items-start justify-between gap-3 text-sm"
                    >
                        <dt class="shrink-0 text-content-muted">{{ column.label }}</dt>
                        <dd class="min-w-0 break-words text-end font-medium text-content-body">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </dd>
                    </div>
                </dl>

                <div
                    v-if="actionColumn && $slots['cell-actions']"
                    class="mt-3 border-t border-edge-soft pt-3"
                >
                    <slot name="cell-actions" :row="row" />
                </div>
            </li>
        </ul>

        <!-- الحاسب: جدول -->
        <div v-if="rows.length > 0" class="hidden overflow-x-auto md:block">
            <table class="w-full table-auto text-start text-sm">
                <caption class="sr-only">{{ label }}</caption>
                <thead class="bg-surface-muted">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="whitespace-nowrap px-4 py-3 text-start text-xs font-semibold text-content-muted"
                        >
                            <button
                                v-if="column.sortable"
                                type="button"
                                class="focus-ring -mx-1 inline-flex items-center gap-1 rounded px-1 py-0.5 hover:text-content-body"
                                @click="emit('sort', column.key)"
                            >
                                {{ column.label }}
                                <AppIcon name="chevron" size="sm" class="rotate-90 opacity-50" />
                            </button>
                            <template v-else>{{ column.label }}</template>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-edge-soft">
                    <tr
                        v-for="(row, index) in rows"
                        :key="(row.id as string | number) ?? index"
                        class="transition-colors duration-150 hover:bg-surface-muted"
                    >
                        <td
                            v-for="(column, columnIndex) in columns"
                            :key="column.key"
                            class="px-4 py-3 align-middle"
                            :class="column.key === 'actions'
                                ? 'whitespace-nowrap text-sm'
                                : columnIndex === 0
                                    ? 'font-semibold text-content-strong'
                                    : 'text-content-body'"
                        >
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ترقيم الصفحات -->
        <div
            v-if="lastPage > 1"
            class="flex flex-col gap-3 border-t border-edge px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-content-muted">
                صفحة <span class="font-semibold tabular-nums text-content-body">{{ currentPage }}</span> من
                <span class="tabular-nums">{{ lastPage }}</span> ({{ total }} سجل)
            </p>
            <nav class="flex items-center gap-2" aria-label="التنقل بين صفحات الجدول">
                <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="currentPage <= 1"
                    @click="goToPage(currentPage - 1)"
                >
                    <AppIcon name="chevron" size="sm" class="ltr:rotate-180" />
                    السابق
                </AppButton>
                <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="currentPage >= lastPage"
                    @click="goToPage(currentPage + 1)"
                >
                    التالي
                    <AppIcon name="chevron" size="sm" class="rtl:rotate-180" />
                </AppButton>
            </nav>
        </div>
    </div>
</template>
