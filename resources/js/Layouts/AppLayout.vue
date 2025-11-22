<script setup>
import { Head } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css'
import 'vue3-select/dist/vue3-select.css'


defineProps({
  title: {
    type: String,
    default: 'Land Records',
  },
});
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <Head :title="title" />

    <!-- Fixed Sidebar -->
    <Sidebar />

    <!-- Main Content Area -->
    <div class="ml-0 md:ml-64 transition-all duration-300">
      <main class="p-6">
        <slot />
        <Toaster richColors  />
      </main>
    </div>
  </div>
</template>
