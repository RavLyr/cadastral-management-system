<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  src: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: 'PDF Preview',
  },
});

const loading = ref(true);

watch(() => props.src, () => {
  loading.value = true;
});

const onLoad = () => {
  loading.value = false;
};
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden h-full flex flex-col">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
      <h3 class="text-sm font-medium text-gray-700">{{ title }}</h3>
    </div>

    <!-- PDF Container -->
    <div class="flex-1 relative bg-gray-100">
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
        <div class="flex items-center gap-2 text-gray-500">
          <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-sm">Loading PDF...</span>
        </div>
      </div>

      <div v-if="!src" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
        <svg class="w-16 h-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">PDF Peta akan ditampilkan di sini</p>
      </div>

      <iframe
        v-if="src"
        :src="src"
        class="w-full h-full border-0"
        @load="onLoad"
      ></iframe>
    </div>
  </div>
</template>
