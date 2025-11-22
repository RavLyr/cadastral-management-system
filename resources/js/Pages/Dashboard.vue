<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/Card.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
  user: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard" />

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
      <p class="text-sm text-gray-500 mt-1">Welcome, Village Officer</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card
        title="Data Tanah"
        description="Input and manage land ownership data records for the village."
        icon="clipboard"
      >
        <template #button>
          <Link
            href="/tanah"
            class="inline-block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            Kelola Data
          </Link>
        </template>
      </Card>

      <Card
        title="Peta Blok"
        description="Upload and view land block map PDF files for visualization and reference."
        icon="map"
      >
        <template #button>
          <Link
            href="/peta"
            class="inline-block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            Kelola Peta
          </Link>
        </template>
      </Card>

      <Card
        title="Pencarian Tanah"
        description="Search for land records by owner name, parcel number, or block number."
        icon="search"
      >
        <template #button>
          <Link
            href="/pencarian"
            class="inline-block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
          >
            Cari Data
          </Link>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
