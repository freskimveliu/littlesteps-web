<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import UiSpinner from './UiSpinner.vue';

type SpecVariant = 'primary' | 'secondary' | 'tertiary';
type LegacyVariant = 'outline' | 'secondary-accent' | 'ghost' | 'danger';
type ButtonVariant = SpecVariant | LegacyVariant;

interface Props {
    tag?: 'button' | 'a';
    type?: 'button' | 'submit' | 'reset';
    to?: string;
    href?: string;
    target?: string;
    download?: string;
    variant?: ButtonVariant;
    size?: 'sm' | 'md' | 'lg';
    active?: boolean;
    disabled?: boolean;
    loading?: boolean;
    fullWidth?: boolean;
    iconOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tag: 'button',
    type: 'button',
    variant: 'primary',
    size: 'lg',
    active: false,
    disabled: false,
    loading: false,
    fullWidth: false,
    iconOnly: false,
});

const emit = defineEmits<{ click: [event: MouseEvent] }>();

const resolvedTag = computed(() => {
    if (props.to) return Link;
    if (props.tag === 'a' || props.href) return 'a';
    return 'button';
});

const isLink = computed(() => resolvedTag.value === Link);
const isAnchor = computed(() => resolvedTag.value === 'a');
const isButton = computed(() => resolvedTag.value === 'button');

const resolvedVariant = computed((): SpecVariant | 'ghost' | 'danger' => {
    switch (props.variant) {
        case 'outline':
            return 'secondary';
        case 'secondary-accent':
            return 'primary';
        case 'ghost':
        case 'danger':
            return props.variant;
        default:
            return props.variant;
    }
});

const resolvedSize = computed(() => (props.size === 'sm' ? 'md' : props.size));

const spinnerTone = computed(() => {
    if (resolvedVariant.value === 'secondary') return 'muted';
    if (resolvedVariant.value === 'ghost') return 'muted';
    return 'inverse';
});

const baseClasses =
    'relative inline-flex items-center justify-center gap-2 box-border border-solid font-sans font-medium leading-[1.2] no-underline cursor-pointer whitespace-nowrap transition-[background,border-color,color,opacity] duration-150 ease-[ease] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/15 disabled:opacity-[0.55] disabled:cursor-not-allowed disabled:pointer-events-none';

const sizeClasses: Record<'md' | 'lg', string> = {
    lg: 'h-[34px] px-[15px] py-[10px] rounded-ui text-[13px]',
    md: 'h-[23px] px-[15px] py-2 rounded-ui text-[10px]',
};

const iconOnlySizeClasses: Record<'md' | 'lg', string> = {
    lg: 'h-[34px] w-[34px] p-0 rounded-ui text-[13px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:flex-shrink-0',
    md: 'h-[23px] w-[23px] p-0 rounded-ui text-[10px] [&_svg]:w-3 [&_svg]:h-3 [&_svg]:flex-shrink-0',
};

const buttonClasses = computed(() => {
    const variant = resolvedVariant.value;
    const size = resolvedSize.value;
    const classes: (string | Record<string, boolean>)[] = [
        baseClasses,
        props.iconOnly ? iconOnlySizeClasses[size] : sizeClasses[size],
    ];

    if (variant === 'primary') {
        classes.push('border-0 bg-primary text-primary-text hover:brightness-95');
    } else if (variant === 'tertiary') {
        classes.push('border-0 bg-secondary text-secondary-text hover:brightness-95');
    } else if (variant === 'ghost') {
        classes.push('border-0 bg-transparent text-[#555555] hover:bg-primary/5 hover:text-primary-accessible');
    } else if (variant === 'danger') {
        classes.push('border-0 bg-red-500 text-white hover:brightness-95');
    } else {
        const active = props.active;
        classes.push('border hover:border-primary hover:text-primary-accessible');
        classes.push(active ? 'border-primary' : 'border-slate-200');
        classes.push(active ? 'text-primary-accessible' : 'text-[#555555]');
        classes.push(active ? 'bg-primary/5' : 'bg-white');
    }

    classes.push({
        'w-full': props.fullWidth,
        'pointer-events-none': props.loading,
        'opacity-[0.55] cursor-not-allowed pointer-events-none': props.disabled && !isButton.value,
    });

    return classes;
});

const handleClick = (event: MouseEvent) => {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
};
</script>

<template>
    <component
        :is="resolvedTag"
        :type="isButton ? type : undefined"
        :href="isLink ? to : isAnchor ? href : undefined"
        :target="isAnchor ? target : undefined"
        :download="isAnchor ? download : undefined"
        :disabled="isButton ? disabled || loading : undefined"
        :class="buttonClasses"
        @click="handleClick"
    >
        <span class="inline-flex items-center justify-center gap-2" :class="{ invisible: loading }">
            <slot />
        </span>
        <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
            <UiSpinner size="xs" :tone="spinnerTone" />
        </span>
    </component>
</template>
