<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import SearchBar from '@/Components/SearchBar.vue';
import Pagination from '@/Components/Pagination.vue';
import { Trash } from 'lucide-vue-next';

const props = defineProps({
    importedData: {
        type: Array,
        default: () => [],
    },
    existingData: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const form = useForm({
    file: null,
});

const uploadProgress = ref(0);
const uploading = ref(false);
const selectedFile = ref(null);
const showModal = ref(false);
const isPreviewMode = ref(false);
const showCommitModal = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedRow = ref(null);
const selectedId = ref(null);

// Tambahkan ref untuk search
const search = ref('');

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file && (file.type === 'application/vnd.ms-excel' || file.type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
        form.file = file;
        selectedFile.value = file;
    } else {
        toast.warning('Harap pilih file Excel (.xls atau .xlsx)');
        event.target.value = '';
    }
};

const submitUpload = () => {
    if (!form.file) {
        toast.warning('Harap pilih file Excel');
        return;
    }

    uploading.value = true;
    uploadProgress.value = 0;

    form.post('/sismiop', {
        onProgress: (progress) => {
            uploadProgress.value = Math.round((progress.loaded / progress.total) * 100);
        },
        onSuccess: () => {
            uploading.value = false;
            uploadProgress.value = 0;
            form.reset();
            selectedFile.value = null;
            document.querySelector('input[type="file"]').value = '';
            isPreviewMode.value = true;
        },
        onError: (errors) => {
            toast.error(errors.error || 'Terjadi kesalahan saat mengunggah file');
            uploading.value = false;
            uploadProgress.value = 0;
        },
    });
};

const cancelPreview = () => {
    isPreviewMode.value = false;
    router.visit('/sismiop', {
        method: 'get',
        preserveState: false,
    });
};

const openCommitModal = () => {
    showCommitModal.value = true;
};

const closeCommitModal = () => {
    showCommitModal.value = false;
};

const commitData = () => {
    router.post('/sismiop/commit', {}, {
        onSuccess: () => {
            toast.success('Data berhasil disimpan ke database');
            showCommitModal.value = false;
            isPreviewMode.value = false;
            router.visit('/sismiop', {
                method: 'get',
                preserveState: false,
            });
        },
        onError: (errors) => {
            toast.error(errors.error || 'Terjadi kesalahan saat menyimpan data');
        },
    });
};

const clearData = () => {
    if (confirm('Apakah Anda yakin ingin menghapus semua data import?')) {
        router.delete('/sismiop/clear', {
            onSuccess: () => {
                toast.success('Data berhasil dihapus');
                isPreviewMode.value = false;
            },
            onError: (errors) => {
                toast.error('Terjadi kesalahan saat menghapus data');
            },
        });
    }
};

const openDetail = (row) => {
    selectedRow.value = row;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedRow.value = null;
};

const openDeleteModal = (id) => {
    selectedId.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedId.value = null;
};

const confirmDelete = () => {
    router.delete(`/sismiop/${selectedId.value}`, {
        onSuccess: () => {
            toast.success('Data berhasil dihapus');
            closeDeleteModal();
        },
        onError: (errors) => {
            toast.error('Terjadi kesalahan saat menghapus data');
        },
    });
};

// Search handling moved to SearchBar component via `search` event
const onSearch = (value) => {
    router.get('/sismiop', { search: value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const headers = [
    { key: 'no', label: 'No' },
    { key: 'nop', label: 'NOP' },
    { key: 'objek_pajak_jalan_dusun_op', label: 'Jalan/Dusun OP' },
    { key: 'objek_pajak_rt', label: 'RT OP' },
    { key: 'objek_pajak_rw', label: 'RW OP' },
    { key: 'subjek_pajak_nama_wajib_pajak', label: 'Nama Wajib Pajak' },
    { key: 'bumi', label: 'Bumi' },
    { key: 'bng', label: 'Bangunan' },
    { key: 'aksi', label: 'Aksi', class:'text-center px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider' },
];

// Preview hanya 5 baris pertama
const previewData = computed(() =>
    props.importedData.slice(0, 5).map((row, idx) => ({
        ...row,
        no: idx + 1,
    }))
);

// Existing data with pagination
const existingDataWithNo = computed(() =>
    props.existingData.data.map((row, idx) => ({
        ...row,
        no: (props.existingData.current_page - 1) * props.existingData.per_page + idx + 1,
    }))
);

const detailHeaders = [
    { key: 'field', label: 'Header' },
    { key: 'value', label: 'Data' },
];

// Computed untuk rows detail
const detailRows = computed(() => {
    if (!selectedRow.value) return [];
    return [
        { field: 'Desa OP', value: selectedRow.value.objek_pajak_desa || '-' },
        { field: 'Jalan/Dusun WP', value: selectedRow.value.subjek_pajak_jalan_dusun || '-' },
        { field: 'RT WP', value: selectedRow.value.subjek_pajak_rt || '-' },
        { field: 'RW WP', value: selectedRow.value.subjek_pajak_rw || '-' },
        { field: 'Desa/Kel WP', value: selectedRow.value.subjek_pajak_desa_kel || '-' },
        { field: 'Kabupaten/Kota WP', value: selectedRow.value.subjek_pajak_kabupaten_kota || '-' },
        { field: 'Jenis Bumi', value: selectedRow.value.jns_bumi || '-' },
        { field: 'Usulan Pembetulan', value: selectedRow.value.usulan_pembetulan || '-' },
    ];
});
</script>

<template>
    <AppLayout title="Upload SISMIOP">

        <Head title="Upload SISMIOP" />

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Upload SISMIOP</h1>
            <p class="text-sm text-gray-500 mt-1">Upload file Excel untuk import data SISMIOP ke sistem.</p>
        </div>



        <!-- Normal Mode: Upload and Existing Data Table -->
        <div v-if="!isPreviewMode">
            <!-- Upload Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Upload File Excel</h2>
                <form @submit.prevent="submitUpload">
                    <div class="mb-6">
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:bg-gray-100 transition-colors">
                            <input type="file"
                                accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                @change="handleFileChange" class="hidden" id="excel-upload" />
                            <label for="excel-upload" class="cursor-pointer">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                        </path>
                                    </svg>
                                    <p class="text-sm text-blue-600 font-medium mb-1">Klik untuk memilih file</p>
                                    <p class="text-xs text-gray-500">atau seret & lepas file Excel di sini</p>
                                    <p class="text-xs text-gray-400 mt-2">(Excel: .xls, .xlsx | MAX: 5MB)</p>
                                </div>
                            </label>
                        </div>
                        <div v-if="selectedFile" class="mt-3 p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
                                </div>
                                <span class="text-xs text-gray-500">{{ (selectedFile.size / 1024).toFixed(2) }}
                                    KB</span>
                            </div>
                        </div>
                        <div v-if="uploading" class="mt-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Mengupload...</span>
                                <span class="text-sm font-medium text-blue-600">{{ uploadProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${uploadProgress}%` }"></div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button v-if="existingData.length > 0" type="button" @click="clearData"
                            class="px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                            Hapus Semua Data
                        </button>
                        <button type="submit" :disabled="uploading || !form.file"
                            class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                </path>
                            </svg>
                            {{ uploading ? 'Mengupload...' : 'Upload & Preview' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Search -->
            <div v-if="!isPreviewMode" class="mb-6">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <SearchBar v-model="search" :placeholder="'Cari berdasarkan NOP atau Nama Wajib Pajak...'" @search="onSearch" />
                        </div>
                    </div>
            </div>

            <!-- Existing Data Table -->
            <div v-if="existingData.data.length > 0" class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Data SISMIOP Tersimpan</h2>
                        <p class="text-xs text-gray-500 mt-1">Total: {{ existingData.total }} record</p>
                    </div>
                </div>
                <Table :headers="headers" :rows="existingDataWithNo" key-field="id">
                    <template #cell-no="{ row }">
                        <span class="text-sm text-gray-700">{{ row.no }}</span>
                    </template>
                    <template #cell-nop="{ row }">
                        <span class="text-sm text-gray-700">{{ row.nop }}</span>
                    </template>
                    <template #cell-objek_pajak_jalan_dusun_op="{ row }">
                        <span class="text-sm text-gray-700">{{ row.objek_pajak_jalan_dusun_op }}</span>
                    </template>
                    <template #cell-objek_pajak_rt="{ row }">
                        <span class="text-sm text-gray-700">{{ row.objek_pajak_rt }}</span>
                    </template>
                    <template #cell-objek_pajak_rw="{ row }">
                        <span class="text-sm text-gray-700">{{ row.objek_pajak_rw }}</span>
                    </template>
                    <template #cell-subjek_pajak_nama_wajib_pajak="{ row }">
                        <span class="text-sm text-gray-700">{{ row.subjek_pajak_nama_wajib_pajak }}</span>
                    </template>
                    <template #cell-bumi="{ row }">
                        <span class="text-sm text-gray-700">{{ row.bumi }}</span>
                    </template>
                    <template #cell-bng="{ row }">
                        <span class="text-sm text-gray-700">{{ row.bng }}</span>
                    </template>
                    <template #cell-aksi="{ row }">
                        <div class="flex gap-2 font-bold">
                            <button @click="openDetail(row)"
                                class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                Lihat Detail
                            </button>
                            <button @click="openDeleteModal(row.id)"
                                class="px-3 py-1 flex justify-center items-center gap-1 flex-row text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                                <Trash size="15" /> <span>Hapus</span>
                            </button>
                        </div>
                    </template>
                </Table>
                <div class="p-3 mb-3">
                    <Pagination :links="existingData.links">
                        <template #info>
                            <span v-if="existingData?.from && existingData?.to && existingData?.total">
                                Showing {{ existingData.from }}-{{ existingData.to }} of {{ existingData.total }}
                            </span>
                            <span v-else>
                                Tidak ada data
                            </span>
                        </template>
                    </Pagination>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-sm p-12">
                <div class="text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <p class="text-sm font-medium text-gray-600 mb-1">Belum ada data yang tersimpan</p>
                    <p class="text-xs text-gray-500">Upload file Excel untuk memulai import data SISMIOP</p>
                </div>
            </div>
        </div>


        <!-- Preview Mode: Preview Table with Actions -->
        <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Preview Data Import</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Total: {{ importedData.length }} record.
                        <span class="text-red-500 font-semibold">Hanya 5 baris pertama yang ditampilkan sebagai
                            preview.</span>
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="cancelPreview"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button @click="openCommitModal"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Simpan ke Database
                    </button>
                </div>
            </div>
            <Table :headers="headers" :rows="previewData" key-field="id">
                <template #cell-no="{ row }">
                    <span class="text-sm text-gray-700">{{ row.no }}</span>
                </template>
                <template #cell-nop="{ row }">
                    <span class="text-sm text-gray-700">{{ row.nop }}</span>
                </template>
                <template #cell-objek_pajak_jalan_dusun_op="{ row }">
                    <span class="text-sm text-gray-700">{{ row.objek_pajak_jalan_dusun_op }}</span>
                </template>
                <template #cell-objek_pajak_rt="{ row }">
                    <span class="text-sm text-gray-700">{{ row.objek_pajak_rt }}</span>
                </template>
                <template #cell-objek_pajak_rw="{ row }">
                    <span class="text-sm text-gray-700">{{ row.objek_pajak_rw }}</span>
                </template>
                <template #cell-subjek_pajak_nama_wajib_pajak="{ row }">
                    <span class="text-sm text-gray-700">{{ row.subjek_pajak_nama_wajib_pajak }}</span>
                </template>
                <template #cell-bumi="{ row }">
                    <span class="text-sm text-gray-700">{{ row.bumi }}</span>
                </template>
                <template #cell-bng="{ row }">
                    <span class="text-sm text-gray-700">{{ row.bng }}</span>
                </template>
                <template #cell-aksi="{ row }">
                    <!-- No actions for preview -->
                </template>
            </Table>
        </div>

        <!-- Detail Modal -->
        <Modal :show="showDetailModal" @close="closeDetailModal" title="Detail Data SISMIOP" max-width="4xl">
            <div class="p-6">
                <Table :headers="detailHeaders" :rows="detailRows" key-field="field">
                    <template #cell-field="{ row }">
                        <span class="text-sm font-medium text-gray-900">{{ row.field }}</span>
                    </template>
                    <template #cell-value="{ row }">
                        <span class="text-sm text-gray-700">{{ row.value }}</span>
                    </template>
                </Table>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" title="Konfirmasi Hapus">
            <div class="p-4">
                <p class="text-base font-medium text-gray-600 mb-6">
                    Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="closeDeleteModal"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button @click="confirmDelete"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Commit Confirmation Modal -->
        <Modal :show="showCommitModal" title="Konfirmasi Simpan Data" @close="closeCommitModal">
            <div class="p-4">

                <p class="text-base font-medium text-gray-600 mb-6">
                    Apakah Anda yakin ingin menyimpan {{ importedData.length }} record data SISMIOP ke database?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="closeCommitModal"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button @click="commitData"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
