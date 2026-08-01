<script lang="ts">
export default { inheritAttrs: false };
</script>

<script setup lang="ts">
import { computed } from 'vue';
import UiFormControl from './UiFormControl.vue';

interface Props {
    id?: string;
    label?: string;
    modelValue?: string | null;
    placeholder?: string;
    rows?: number;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    hint?: string;
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
    required: false,
    disabled: false,
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const textareaClasses = computed(() =>
    [props.error ? 'ui-input ui-input--error' : 'ui-input', 'resize-y'].join(' '),
);
</script>

<template>
    <UiFormControl :for-id="id" :label="label" :required="required" :error="error" :hint="hint">
        <textarea
            :id="id"
            :value="modelValue ?? ''"
            :rows="rows"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="textareaClasses"
            v-bind="$attrs"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        />
    </UiFormControl>
</template>
