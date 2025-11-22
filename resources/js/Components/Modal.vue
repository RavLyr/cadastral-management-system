<script setup>
import { onMounted, onUnmounted } from 'vue';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  maxWidth: {
    type: String,
    default: '2xl', // sm, md, lg, xl, 2xl, 4xl, 6xl
  },
});

const emit = defineEmits(['close']);

const close = () => {
  emit('close');
};

const closeOnEscape = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};

onMounted(() => {
  document.addEventListener('keydown', closeOnEscape);
});

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape);
});

const maxWidthClass = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  '4xl': 'max-w-4xl',
  '6xl': 'max-w-6xl',
};
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-show="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
        @click.self="close"
      >
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-show="show"
            :class="['bg-white rounded-xl shadow-xl w-full', maxWidthClass[maxWidth]]"
            class="max-h-[90vh] overflow-hidden flex flex-col"
          >
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
              <button
                @click="close"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="px-6 py-4 overflow-y-auto flex-1">
              <slot />
            </div>

            <!-- Modal Footer -->
            <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
