<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import Table from '@/Components/Table.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Eye } from 'lucide-vue-next';
import { Trash2 } from 'lucide-vue-next';
import { CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    petaBlok: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nama_blok: '',
    skala: '',
    file: null,
});

const uploadProgress = ref(0);
const uploading = ref(false);
const selectedFile = ref(null);

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file && file.type === 'application/pdf') {
        form.file = file;
        selectedFile.value = file;
    } else {
        alert('Harap pilih file PDF');
        event.target.value = '';
    }
};

const submitUpload = () => {
    if (!form.nama_blok || !form.skala || !form.file) {
        toast.error('Harap lengkapi semua field');
        return;
    }

    uploading.value = true;
    uploadProgress.value = 0;

    form.post('/peta', {
        forceFormData: true,
        onProgress: (progress) => {
            uploadProgress.value = Math.round((progress.loaded / progress.total) * 100);
        },
        onSuccess: () => {
            uploading.value = false;
            uploadProgress.value = 0;
            form.reset();
            selectedFile.value = null;
            document.querySelector('input[type="file"]').value = '';
            toast.success('Peta blok berhasil diunggah!');
        },
        onError: (errors) => {
            uploading.value = false;
            uploadProgress.value = 0;
            toast.error(errors?.error || 'Gagal mengunggah peta blok. Silakan cek input Anda.');
        },
    });
};

const viewPdf = (url) => {
    window.open(url, '_blank');
};

const deletePeta = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus peta blok ini?')) {
        router.delete(`/peta/${id}`, {
            onSuccess: () => {
                toast.success('Peta blok berhasil dihapus!');
            },
            onError: (errors) => {
                toast.error(errors?.error || 'Gagal menghapus peta blok.');
            },
        });
    }
};

const headers = [
    { key: 'nama_blok', label: 'Nama Blok' },
    { key: 'nama_file', label: 'Nama File' },
    { key: 'skala', label: 'Skala' },
    { key: 'aksi', label: 'Aksi' },
];
const skalaOptions = [
    { value: '1:1000', label: '1:1000' },
    { value: '1:2000', label: '1:2000' },
    { value: '1:5000', label: '1:5000' },
    { value: '1:10000', label: '1:10000' },
];
</script>

<template>
    <AppLayout title="Unggah Peta Blok">

        <Head title="Peta Blok" />

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Unggah Peta Blok</h1>
            <p class="text-sm text-gray-500 mt-1">Unggah dan kelola file PDF peta blok tanah.</p>
        </div>

        <!-- Upload Form Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Formulir Unggah Peta</h2>

            <form @submit.prevent="submitUpload">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <FormInput v-model="form.nama_blok" label="Nama Blok" type="text"
                        placeholder="Contoh: Blok Cendana 01" required />
                    <FormInput v-model="form.skala" label="Skala Peta" type="select" :options="skalaOptions" required />
                </div>

                <!-- Upload PDF File -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload PDF File</label>
                    <div
                        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center bg-gray-50 hover:bg-gray-100 transition-colors">
                        <input type="file" accept="application/pdf" @change="handleFileChange" class="hidden"
                            id="file-upload" />
                        <label for="file-upload" class="cursor-pointer">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="text-sm text-blue-600 font-medium mb-1">Klik untuk memilih file</p>
                                <p class="text-xs text-gray-500">atau seret & lepas file PDF di sini</p>
                                <p class="text-xs text-gray-400 mt-2">(PDF MAX: 10MB)</p>
                            </div>
                        </label>
                    </div>

                    <!-- Selected File Info -->
                    <div v-if="selectedFile" class="mt-3 p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ (selectedFile.size / 1024).toFixed(2) }} KB</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div v-if="uploading" class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">{{ selectedFile?.name }}</span>
                            <span class="text-sm font-medium text-blue-600">{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                :style="{ width: `${uploadProgress}%` }"></div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" :disabled="uploading || !form.file"
                        class="flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <CheckCircle :size="20" />
                        {{ uploading ? 'Mengunggah...' : 'Simpan' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Uploaded Files Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Daftar Peta Blok</h2>
            </div>

            <Table :headers="headers" :rows="petaBlok" key-field="id">
                <template #cell-nama_file="{ row }">
                    <span class="text-sm text-gray-700">{{ row.file_name }}</span>
                </template>

                <template #cell-skala="{ row }">
                    <span class="text-sm text-gray-700">{{ row.skala }}</span>
                </template>

                <template #cell-aksi="{ row }">
                    <div class="flex items-center gap-2">
                        <button @click="viewPdf(row.file_url)"
                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Lihat">
                            <Eye :size="20" />
                        </button>
                        <button @click="deletePeta(row.id)"
                            class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" title="Hapus">
                            <Trash2 :size="20" />
                        </button>
                    </div>
                </template>
            </Table>
        </div>
    </AppLayout>
</template>
