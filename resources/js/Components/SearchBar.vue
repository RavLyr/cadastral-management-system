<template>
  <div class="relative w-full">
    <span v-if="showIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
      <slot name="icon">
        <!-- default magnifier icon (SVG) -->
       <Search size="20" />
      </slot>
    </span>

    <input
      :value="modelValue"
      @input="onInput"
      @keydown.enter="emitNow"
      :placeholder="placeholder"
      :type="type"
      :class="['block w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400', inputClass]"
    />

    <button v-if="showClear && modelValue" @click="clear" type="button" class="absolute inset-y-0 right-0 pr-2 flex items-center">
      <X size="20" class="text-gray-400 hover:text-gray-600" />
    </button>
  </div>
</template>

<script setup>
import { X } from 'lucide-vue-next';
import { Search } from 'lucide-vue-next';
import { ref, watch, toRef } from 'vue';
const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  debounce: { type: Number, default: 300 },
  placeholder: { type: String, default: 'Search...' },
  type: { type: String, default: 'text' },
  showIcon: { type: Boolean, default: true },
  showClear: { type: Boolean, default: true },
  inputClass: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'search']);

const value = ref(props.modelValue);
let timeout = null;

watch(() => props.modelValue, (v) => {
  value.value = v;
});

const onInput = (e) => {
  const v = e.target.value;
  value.value = v;
  emit('update:modelValue', v);

  clearTimeout(timeout);
  timeout = setTimeout(() => {
    emit('search', v);
  }, props.debounce);
};

const emitNow = () => {
  clearTimeout(timeout);
  emit('search', value.value);
};

const clear = () => {
  value.value = '';
  emit('update:modelValue', '');
  emit('search', '');
};
</script>
