<script setup lang="ts">
/**
 * SectionCard — بطاقة قسم بعنوان وإجراءات اختيارية.
 * تعبّر عن نمط متكرر (surface + header + divider) كان مكرراً في كل صفحات
 * لوحات التحكم، وتحافظ على نفس الحدود والزوايا والظل عبر التطبيق.
 * padded=false للقوائم/الجداول التي تتحكم بحشوتها بنفسها.
 */
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        padded?: boolean;
    }>(),
    {
        title: '',
        description: '',
        padded: true,
    },
);
</script>

<template>
    <section class="card overflow-hidden">
        <header v-if="title || $slots.actions" class="card-header">
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-content">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 text-sm text-content-muted">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </header>

        <div :class="padded ? 'card-body' : ''">
            <slot />
        </div>
    </section>
</template>
