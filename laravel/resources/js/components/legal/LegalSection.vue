<script setup lang="ts">
interface Props {
    id: string;
    index: number;
    heading: string;
}

defineProps<Props>();
</script>

<template>
    <section :id="id" :data-toc="heading" class="reveal border-t border-primary/10 pt-10 first:border-0 first:pt-0">
        <h2 class="flex items-baseline gap-3 font-display text-2xl font-extrabold tracking-tight text-ink">
            <span class="text-sm font-extrabold text-primary/45 tabular-nums">{{ String(index).padStart(2, '0') }}</span>
            {{ heading }}
        </h2>

        <div class="legal-prose mt-4 space-y-4 text-[15px] leading-relaxed text-ink/65">
            <slot />
        </div>
    </section>
</template>
