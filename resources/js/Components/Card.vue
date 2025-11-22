<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: 'default',
  },
  buttonText: {
    type: String,
    default: '',
  },
  buttonHref: {
    type: String,
    default: '#',
  },
});
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition-shadow">
    <!-- Icon -->
    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-4">
      <!-- Clipboard Icon (Data Tanah) -->
      <svg v-if="icon === 'clipboard'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>

      <!-- Map Icon (Peta Blok) -->
      <svg v-else-if="icon === 'map'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
      </svg>

      <!-- Search Icon (Pencarian) -->
      <svg v-else-if="icon === 'search'" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>

      <!-- Default Icon -->
      <svg v-else class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </div>

    <!-- Title -->
    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ title }}</h3>

    <!-- Description -->
    <p class="text-sm text-gray-500 mb-6">{{ description }}</p>

    <!-- Button -->
    <slot name="button">
      <a
        v-if="buttonText"
        :href="buttonHref"
        class="inline-block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
      >
        {{ buttonText }}
      </a>
    </slot>
  </div>
</template>
