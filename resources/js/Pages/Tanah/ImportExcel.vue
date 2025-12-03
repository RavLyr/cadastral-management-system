<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { AlertTriangle, ArrowLeft, UploadCloudIcon } from 'lucide-vue-next';

const props = defineProps({
    blokCount: {
        type: Number,
        default: 0,
    },
    preview: {
        type: Object,
        default: () => ({
            id: null,
            fileName: null,
            validCount: 0,
            errorCount: 0,
            errors: [],
            canImport: false,
        }),
    },
});

const previewForm = useForm({
    file: null,
});

const executeForm = useForm({
    preview_id: props.preview?.id || '',
});

const showErrorModal = ref(false);
const fileInput = ref(null);
const currentRequestId = ref(null);
const isLocked = computed(() => (props.blokCount ?? 0) === 0);
const errorRows = computed(() => props.preview?.errors ?? []);

watch(
    () => props.preview,
    (value) => {
        executeForm.preview_id = value?.id || '';
        if ((value?.errors ?? []).length > 0) {
            showErrorModal.value = true;
        }
    },
    { deep: true, immediate: true }
);

const handleFileChange = (event) => {
    previewForm.clearErrors('file');
    previewForm.file = event.target.files[0];
};

const submitPreview = () => {
    if (!previewForm.file) {
        previewForm.setError('file', 'Silakan pilih file Excel terlebih dahulu.');
        return;
    }

    const requestId = Date.now();
    currentRequestId.value = requestId;

    previewForm.post('/tanah/import/preview', {
        forceFormData: true,
        onSuccess: () => {
            if (currentRequestId.value === requestId) {
                previewForm.file = null;
                if (fileInput.value) {
                    fileInput.value.value = '';
                }
            }
        },
    });
};

const executeImport = () => {
    executeForm.post('/tanah/import/execute', {
        onSuccess: () => {
            executeForm.reset();
        },
    });
};

const backToIndex = () => {
    router.visit('/tanah');
};
</script>

<template>
    <AppLayout title="Import Data Tanah">
        <Head title="Import Data Tanah" />

        <div class="flex items-center gap-3 mb-6">
            <button @click="backToIndex" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900">
                <ArrowLeft size="18" />
                Kembali ke daftar Tanah
            </button>
        </div>
        <div class="flex items-center mx-2 mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Import Data Tanah</h1>
        </div>

        <div v-if="isLocked" class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 flex gap-3">
            <AlertTriangle class="w-5 h-5 flex-shrink-0" />
            <div>
                <p class="font-semibold">Import terkunci karena belum ada data blok.</p>
                <p class="text-sm">Unggah peta blok terlebih dahulu pada menu Peta sebelum melakukan import.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">File Excel (.xlsx)</label>
            <input ref="fileInput" type="file" accept=".xls,.xlsx" @change="handleFileChange"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-600 hover:file:bg-orange-100"
                :disabled="isLocked || previewForm.processing" />
            <InputError :message="previewForm.errors.file" class="mt-2" />

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="button" @click="submitPreview"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isLocked || previewForm.processing">
                    <UploadCloudIcon size="18" />
                    {{ previewForm.processing ? 'Memproses...' : 'Preview Import' }}
                </button>

                <button type="button" @click="executeImport"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!props.preview?.canImport || executeForm.processing">
                    {{ executeForm.processing ? 'Mengimpor...' : 'Import Sekarang' }}
                </button>
            </div>
            <InputError :message="executeForm.errors.preview_id" class="mt-2" />

            <div v-if="props.preview?.fileName" class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-100 p-4">
                    <p class="text-sm text-gray-500">Nama File</p>
                    <p class="text-base font-semibold text-gray-900">{{ props.preview.fileName }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 p-4">
                    <p class="text-sm text-gray-500">Status Validasi</p>
                    <p class="text-base font-semibold" :class="props.preview.canImport ? 'text-green-600' : 'text-red-600'">
                        {{ props.preview.canImport ? 'Siap diimport' : 'Perlu perbaikan' }}
                    </p>
                </div>
                <div class="rounded-lg border border-gray-100 p-4">
                    <p class="text-sm text-gray-500">Data Valid</p>
                    <p class="text-2xl font-bold text-gray-900">{{ props.preview.validCount }}</p>
                </div>
                <div class="rounded-lg border border-gray-100 p-4">
                    <p class="text-sm text-gray-500">Jumlah Error</p>
                    <p class="text-2xl font-bold" :class="props.preview.errorCount ? 'text-red-600' : 'text-gray-900'">
                        {{ props.preview.errorCount }}
                    </p>
                </div>
            </div>
        </div>

        <Modal :show="showErrorModal && errorRows.length" title="Rincian Error Import" max-width="4xl"
            @close="showErrorModal = false">
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-2">Baris</th>
                            <th class="px-4 py-2">Field</th>
                            <th class="px-4 py-2">Pesan Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in errorRows" :key="index" class="border-b border-gray-100">
                            <td class="px-4 py-2 font-mono text-gray-700">{{ item.row }}</td>
                            <td class="px-4 py-2 capitalize">{{ item.field }}</td>
                            <td class="px-4 py-2 text-red-600">{{ item.message }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <template #footer>
                <button type="button" @click="showErrorModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Tutup
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
