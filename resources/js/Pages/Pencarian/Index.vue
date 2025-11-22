<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PdfPreview from '@/Components/PdfPreview.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Search, FileSearch, Maximize2 } from 'lucide-vue-next';

const props = defineProps({
  results: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const search = ref(props.filters.search || '');
const selectedItem = ref(null);
const pdfUrl = ref('');

// Watch search input and debounce
let searchTimeout = null;
watch(search, (value) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/pencarian', { search: value }, {
      preserveState: true,
      preserveScroll: true,
      onError: (errors) => {
        toast.error(errors?.error || 'Gagal melakukan pencarian.');
      },
    });
  }, 300);
});

const viewPeta = (item) => {
  selectedItem.value = item;
  pdfUrl.value = item.peta_url || '';
  if (!pdfUrl.value) {
    toast.error('File peta tidak tersedia.');
  }
};

const openFullscreen = () => {
  if (pdfUrl.value) {
    window.open(pdfUrl.value, '_blank');
  } else {
    toast.error('File peta tidak tersedia.');
  }
};
</script>

<template>
  <AppLayout title="Pencarian Data Tanah">
    <Head title="Pencarian" />

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Pencarian Data Tanah</h1>
      <p class="text-sm text-gray-500 mt-1">Cari berdasarkan Nama, Nomor Persil, atau Blok...</p>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left Column: Search Results -->
      <div class="space-y-4">
        <!-- Search Box -->
        <div class="bg-white rounded-xl shadow-sm p-4">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
              <Search :size="20" />
            </span>
            <input
              v-model="search"
              type="text"
              placeholder="Cari berdasarkan Nama, Nomor Persil, atau Blok..."
              class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
            />
          </div>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NAMA</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PERSIL</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">BLOK</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">LUAS (M²)</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">AKSI</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <tr v-if="results.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                    <div class="flex flex-col items-center justify-center">
                      <FileSearch :size="48" class="mb-2 text-gray-300" />
                      <p>Tidak ada data ditemukan</p>
                    </div>
                  </td>
                </tr>
                <tr
                  v-for="item in results"
                  :key="item.id"
                  :class="[
                    'hover:bg-blue-50 transition-colors cursor-pointer',
                    selectedItem?.id === item.id ? 'bg-blue-50' : ''
                  ]"
                  @click="viewPeta(item)"
                >
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.nama_wajib_ipeda }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.nomor_persil }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.blok }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ item.luas_m2 || '-' }}</td>
                  <td class="px-4 py-3">
                    <button
                      @click.stop="viewPeta(item)"
                      class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors flex items-center gap-1"
                    >
                      <FileSearch :size="16" />
                      Lihat Peta
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column: PDF Preview & Details -->
      <div
        class="space-y-4 overflow-y-auto h-[calc(100vh-120px)]"
        style="min-height:400px;"
      >
        <!-- PDF Preview -->
        <div class="h-96 lg:h-[500px]">
          <PdfPreview
            :src="pdfUrl"
            :title="selectedItem ? `Peta ${selectedItem.blok}` : 'PDF Preview'"
          />
        </div>

        <!-- Detail Panel -->
        <div v-if="selectedItem" class="bg-white rounded-xl shadow-sm p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Detail Peta</h3>

          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Blok</span>
              <span class="text-sm font-medium text-gray-900">{{ selectedItem.blok }}</span>
            </div>
            <div class="h-px bg-gray-200"></div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Nomor Persil</span>
              <span class="text-sm font-medium text-gray-900">{{ selectedItem.nomor_persil }}</span>
            </div>
            <div class="h-px bg-gray-200"></div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Luas</span>
              <span class="text-sm font-medium text-gray-900">{{ selectedItem.luas_m2 }} m²</span>
            </div>
            <div class="h-px bg-gray-200"></div>

            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-500">Skala Peta</span>
              <span class="text-sm font-medium text-gray-900">{{ selectedItem.skala || '1:1000' }}</span>
            </div>
          </div>

          <!-- Section Detail Tanah -->
          <div class="mt-8">
            <h4 class="text-base font-semibold text-gray-900 mb-3">Detail Data Tanah</h4>
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Nama Wajib IPEDA</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.nama_wajib_ipeda || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Tempat Tinggal</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.tempat_tinggal || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">IPEDA R</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.ipeda_r || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">IPEDA S</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.ipeda_s || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Sebab Perubahan</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.sebab_perubahan || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Tanggal Perubahan</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.tgl_perubahan || '-' }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Jenis Tanah</span>
                <span class="text-sm font-medium text-gray-900">{{ selectedItem.jenis_tanah || '-' }}</span>
              </div>
            </div>
          </div>

          <!-- Open Fullscreen Button -->
          <button
            v-if="pdfUrl"
            @click="openFullscreen"
            class="mt-4 w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <Maximize2 :size="20" />
            Buka Fullscreen
          </button>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-xl shadow-sm p-12">
          <div class="text-center text-gray-400">
            <FileSearch :size="48" class="mx-auto mb-4" />
            <p class="text-sm">Pilih data untuk melihat detail dan peta</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
