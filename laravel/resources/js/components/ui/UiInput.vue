<script lang="ts">
export default { inheritAttrs: false };
</script>

<script setup lang="ts">
import { computed, ref } from 'vue';
import ExclamationCircleIcon from '@heroicons/vue/24/outline/esm/ExclamationCircleIcon.js';
import UiFormControl from './UiFormControl.vue';

interface Props {
    id?: string;
    type?: string;
    label?: string;
    modelValue?: string | number | null;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    hint?: string;
    autocomplete?: string;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    blur: [event: FocusEvent];
    focus: [event: FocusEvent];
}>();

const inputClasses = computed(() => {
    const stateClasses = props.error ? 'ui-input ui-input--error' : 'ui-input';
    const disabledClasses = props.disabled ? 'cursor-not-allowed opacity-60' : '';
    return [stateClasses, disabledClasses].filter(Boolean).join(' ');
});

const handleInput = (event: Event) => {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
};

const inputEl = ref<HTMLInputElement | null>(null);

defineExpose({ inputEl, focus: () => inputEl.value?.focus() });
</script>

<template>
    <UiFormControl :for-id="id" :label="label" :required="required" :error="error" :hint="hint">
        <div class="relative">
            <input
                :id="id"
                ref="inputEl"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :autocomplete="autocomplete"
                :class="inputClasses"
                v-bind="$attrs"
                @input="handleInput"
                @blur="(e) => emit('blur', e)"
                @focus="(e) => emit('focus', e)"
            />

            <div v-if="error" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <ExclamationCircleIcon class="h-5 w-5 text-red-500" />
            </div>
        </div>
    </UiFormControl>
</template>
