<script setup>
import { computed } from 'vue';
import Vue3Select from 'vue3-select';

const props = defineProps({
    label: {
        type: String,
        default: '',
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    options: {
        type: Array,
        default: () => [],
        // For select: [{ value: '1', label: 'Option 1' }]
    },
    rows: {
        type: Number,
        default: 4,
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const inputValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="space-y-2">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-gray-700">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Text, Number, Date Inputs -->
        <input v-if="type !== 'textarea' && type !== 'select'" v-model="inputValue" :type="type"
            :placeholder="placeholder" :required="required" :disabled="disabled" :class="[
                'block w-full px-4 py-3 border rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors',
                error ? 'border-red-300' : 'border-slate-200',
                disabled ? 'bg-gray-100 cursor-not-allowed' : ''
            ]" />


        <!-- Textarea -->
        <textarea v-else-if="type === 'textarea'" v-model="inputValue" :placeholder="placeholder" :required="required"
            :disabled="disabled" :rows="rows" :class="[
                'block w-full px-4 py-3 border rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors resize-none',
                error ? 'border-red-300' : 'border-slate-200',
                disabled ? 'bg-gray-100 cursor-not-allowed' : ''
            ]"></textarea>

        <!-- Select -->
        <Vue3Select  v-else-if="type === 'select'" v-model="inputValue" :options="options"
            :reduce="option => option.value" :clearable="false" :searchable="false" :placeholder="placeholder || 'Pilih...'"
            class="block w-full py-2 border rounded-lg bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-colors" />


        <!-- Error Message -->
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
