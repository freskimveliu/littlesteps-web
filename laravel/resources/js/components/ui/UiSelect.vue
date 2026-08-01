<script setup lang="ts">
import { computed } from 'vue';
import ChevronDownIcon from '@heroicons/vue/24/outline/esm/ChevronDownIcon.js';
import UiFormControl from './UiFormControl.vue';

interface Option {
    value: string | number;
    label: string;
}

interface Props {
    id?: string;
    label?: string;
    modelValue?: string | number | null;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    hint?: string;
    options?: Option[];
}

const props = withDefaults(defineProps<Props>(), {
    required: false,
    disabled: false,
    options: () => [],
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();

const selectClasses = computed(() => {
    const stateClasses = props.error ? 'ui-input ui-input--error' : 'ui-input';
    const disabledClasses = props.disabled ? 'cursor-not-allowed opacity-60' : '';
    return ['appearance-none [-webkit-appearance:none] pr-10 cursor-pointer', stateClasses, disabledClasses]
        .filter(Boolean)
        .join(' ');
});

const handleChange = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLSelectElement).value);
};
</script>

<template>
    <UiFormControl :for-id="id" :label="label" :required="required" :error="error" :hint="hint">
        <div class="relative">
            <select
                :id="id"
                :value="modelValue"
                :required="required"
                :disabled="disabled"
                :class="selectClasses"
                @change="handleChange"
                @blur="(e) => emit('blur', e)"
                @focus="(e) => emit('focus', e)"
            >
                <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
                <slot>
                    <option v-for="opt in options" :key="String(opt.value)" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </slot>
            </select>

            <ChevronDownIcon
                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-gray-400"
            />
        </div>
    </UiFormControl>
</template>
