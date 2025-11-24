<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PdfPreview from '@/Components/PdfPreview.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { FileSearch, Maximize2, Printer } from 'lucide-vue-next';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';

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


const headers = [
    { label: 'NAMA', key: 'nama_wajib_ipeda' },
    { label: 'PERSIL', key: 'nomor_persil' },
    { label: 'BLOK', key: 'blok' },
    { label: 'LUAS (M²)', key: 'luas_m2' },
    { label: 'AKSI', key: 'actions' },
];



const onSearch = (value) => {
    router.get('/pencarian', { search: value }, {
        preserveState: true,
        preserveScroll: true,
        onError: (errors) => {
            toast.error(errors?.error || 'Gagal melakukan pencarian.');
        },
    });
};
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

const printPdf = (item) => {
    window.open(`/print/${item.id}`, '_blank');
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
                        <SearchBar v-model="search" placeholder="Cari berdasarkan Nama, Nomor Persil, atau Blok..."
                            @search="onSearch" />
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">

                        <Table :headers="headers" :rows="results" key-field="id">
                            <template #cell-actions="{ row }">
                                <div class="flex gap-2">
                                    <button @click.stop="viewPeta(row)"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors flex items-center gap-1">
                                        <FileSearch :size="16" />
                                        Lihat Peta
                                    </button>
                                    <button @click.stop="printPdf(row)"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded transition-colors flex items-center gap-1">
                                        <Printer :size="16" />
                                        Cetak PDF
                                    </button>
                                </div>
                            </template>
                        </Table>
                    </div>
                </div>
            </div>

            <!-- Right Column: PDF Preview & Details -->
            <div class="space-y-4 overflow-y-auto h-[calc(100vh-120px)]" style="min-height:400px;">
                <!-- PDF Preview -->
                <div class="h-96 lg:h-[500px]">
                    <PdfPreview :src="pdfUrl" :title="selectedItem ? `Peta ${selectedItem.blok}` : 'PDF Preview'" />
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
                                <span class="text-sm font-medium text-gray-900">{{ selectedItem.nama_wajib_ipeda || '-'
                                }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Tempat Tinggal</span>
                                <span class="text-sm font-medium text-gray-900">{{ selectedItem.tempat_tinggal || '-'
                                }}</span>
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
                                <span class="text-sm font-medium text-gray-900">{{ selectedItem.sebab_perubahan || '-'
                                }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Tanggal Perubahan</span>
                                <span class="text-sm font-medium text-gray-900">{{ selectedItem.tgl_perubahan || '-'
                                }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Jenis Tanah</span>
                                <span class="text-sm font-medium text-gray-900">{{ selectedItem.jenis_tanah || '-'
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Open Fullscreen Button -->
                    <button v-if="pdfUrl" @click="openFullscreen"
                        class="mt-4 w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
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
