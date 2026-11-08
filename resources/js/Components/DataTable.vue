<script setup lang="ts">
export interface DataTableColumn {
    key: string;
    label: string;
    sortable?: boolean;
}

const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: any[];
        currentPage?: number;
        lastPage?: number;
        total?: number;
        searchable?: boolean;
    }>(),
    {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        searchable: true,
    },
);

const emit = defineEmits<{
    'update:page': [page: number];
    sort: [key: string];
    search: [value: string];
}>();

function goToPage(page: number): void {
    if (page >= 1 && page <= props.lastPage) {
        emit('update:page', page);
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-edge bg-surface shadow-sm">
        <div v-if="searchable" class="border-b border-edge p-4">
            <input
                type="search"
                placeholder="بحث..."
                class="block min-h-[44px] w-full max-w-sm rounded-md border-edge-strong bg-surface text-content shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm"
                @input="emit('search', ($event.target as HTMLInputElement).value)"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-edge text-right">
                <thead class="bg-surface-muted">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-content-muted"
                            :class="{ 'cursor-pointer hover:text-content-body': column.sortable }"
                            @click="column.sortable && emit('sort', column.key)"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-edge-soft bg-surface">
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length" class="px-4 py-8 text-center text-sm text-content-muted">
                            لا توجد بيانات لعرضها
                        </td>
                    </tr>
                    <tr v-for="(row, index) in rows" :key="(row.id as string | number) ?? index" class="hover:bg-surface-muted">
                        <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-sm text-content-body">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="lastPage > 1" class="flex items-center justify-between border-t border-edge px-4 py-3">
            <span class="text-sm text-content-muted">
                صفحة {{ currentPage }} من {{ lastPage }} ({{ total }} سجل)
            </span>
            <div class="flex gap-2">
                <button
                    type="button"
                    :disabled="currentPage <= 1"
                    class="min-h-[44px] rounded-md border border-edge-strong px-3 py-1.5 text-sm disabled:opacity-40"
                    @click="goToPage(currentPage - 1)"
                >
                    السابق
                </button>
                <button
                    type="button"
                    :disabled="currentPage >= lastPage"
                    class="min-h-[44px] rounded-md border border-edge-strong px-3 py-1.5 text-sm disabled:opacity-40"
                    @click="goToPage(currentPage + 1)"
                >
                    التالي
                </button>
            </div>
        </div>
    </div>
</template>
