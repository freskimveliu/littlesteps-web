<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ inheritAttrs: false });

interface Props {
    type?: 'button' | 'submit';
    to?: string;
    href?: string;
    target?: string;
    title?: string;
    disabled?: boolean;
    active?: boolean;
    variant?: 'outline' | 'filled';
    tone?: 'default' | 'danger';
    size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
    variant: 'outline',
    tone: 'default',
    size: 'md',
});

const attrs = useAttrs();

const passThroughAttrs = computed(() => {
    const { class: _class, ...rest } = attrs;
    return rest;
});

const baseClasses =
    'inline-flex items-center justify-center box-border p-0 rounded-ui flex-shrink-0 cursor-pointer transition-[background,border-color,color] duration-150 ease-[ease] disabled:opacity-[0.55] disabled:cursor-not-allowed';

const sizeClasses: Record<NonNullable<Props['size']>, string> = {
    md: 'w-[34px] h-[34px]',
    sm: 'w-[28px] h-[28px]',
};

const btnClass = computed(() => {
    const classes: (string | Record<string, boolean>)[] = [baseClasses, sizeClasses[props.size]];

    if (props.variant === 'outline') {
        classes.push(
            'border [&:hover:not(:disabled)]:border-primary [&:hover:not(:disabled)]:text-primary-accessible',
        );
        classes.push(props.active ? 'border-primary' : 'border-slate-200');
        classes.push(props.active ? 'text-primary-accessible' : 'text-[#555555]');
        classes.push(props.active ? 'bg-primary/5' : 'bg-white');
    } else {
        const danger = props.tone === 'danger';
        classes.push('border-0');
        classes.push(props.active ? 'bg-[#e2e8f0]' : 'bg-[#f0f4f8]');
        classes.push(danger ? 'text-red-600' : 'text-[#555555]');
        classes.push(
            danger
                ? '[&:hover:not(:disabled)]:bg-red-100 [&:hover:not(:disabled)]:text-red-600'
                : '[&:hover:not(:disabled)]:bg-[#e2e8f0]',
        );
    }

    if (attrs.class) {
        classes.push(attrs.class as string);
    }

    return classes;
});
</script>

<template>
    <Link v-if="to" v-bind="passThroughAttrs" :href="to" :title="title" :class="btnClass">
        <slot />
    </Link>
    <a v-else-if="href" v-bind="passThroughAttrs" :href="href" :target="target" :title="title" :class="btnClass">
        <slot />
    </a>
    <button v-else v-bind="passThroughAttrs" :type="type" :disabled="disabled" :title="title" :class="btnClass">
        <slot />
    </button>
</template>
