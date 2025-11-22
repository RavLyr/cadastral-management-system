<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  links: {
    type: Array,
    required: true,
  },
});


</script>

<template>
  <div v-if="links && links.length" class="flex items-center justify-between mt-4">
    <div class="text-sm text-gray-500">
      <slot name="info" />
    </div>

    <nav class="flex items-center gap-1">
      <template v-for="(link, index) in links" :key="index">
        <!-- Jika url null/undefined, render sebagai elemen non-link -->
        <span
          v-if="!link.url"
          class="min-w-[2.5rem] px-3 py-2 text-sm font-medium rounded-lg transition-colors text-center text-gray-400 cursor-not-allowed"
          v-html="link.label"
        />

        <!-- Jika url valid, render sebagai Link -->
        <Link
          v-else
          :href="link.url"
          :class="[
            'min-w-[2.5rem] px-3 py-2 text-sm font-medium rounded-lg transition-colors text-center',
            link.active
              ? 'bg-blue-600 text-white'
              : 'text-gray-700 hover:bg-gray-100'
          ]"
          v-html="link.label"
        />
      </template>
    </nav>
  </div>
</template>
