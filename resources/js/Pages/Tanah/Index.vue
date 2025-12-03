<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Table from '@/Components/Table.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/Components/InputError.vue';
import { toast } from 'vue-sonner';
import { PlusIcon, Search, Sheet, SquarePen, Trash2, UploadCloudIcon } from 'lucide-vue-next';
import SearchBar from '@/Components/SearchBar.vue';

const props = defineProps({
    tanah: {
        type: Object,
        required: true,
    },
    blokList: {
        type: Array,
        default: () => [],
    },
    blokCount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters?.search || '');
const hasBlok = computed(() => (props.blokCount ?? 0) > 0);
const showModal = ref(false);
const showEditModal = ref(false);
const editingItem = ref(null);
const showDeleteModal = ref(false);
const deletingItem = ref(null);
const errorMessage = ref('');
// console.log(props.tanah.total);


// Form for creating new data
const form = useForm({
    no_urut: '',
    nama_wajib_ipeda: '',
    tempat_tinggal: '',
    nomor_persil: '',
    blok_id: '',
    jenis_tanah: '',
    luas_ha: '',
    luas_da: '',
    ipeda_r: '',
    ipeda_s: '',
    sebab_perubahan: '',
    tgl_perubahan: '',
});

const editForm = useForm({
    no_urut: '',
    nama_wajib_ipeda: '',
    tempat_tinggal: '',
    nomor_persil: '',
    blok_id: '',
    jenis_tanah: '',
    luas_ha: '',
    luas_da: '',
    ipeda_r: '',
    ipeda_s: '',
    sebab_perubahan: '',
    tgl_perubahan: '',
});

const headers = [
    { key: 'no', label: 'NO' },
    { key: 'nama_wajib_ipeda', label: 'NAMA WAJIB IPEDA' },
    { key: 'persil', label: 'PERSIL' },
    { key: 'blok', label: 'BLOK' },
    { key: 'jenis_tanah', label: 'JENIS TANAH' },
    { key: 'luas_ha', label: 'LUAS (HA)' },
    { key: 'luas_da', label: 'LUAS (DA)' },
    { key: 'aksi', label: 'AKSI' },
];

// Search handling moved to SearchBar component via `search` event
const onSearch = (value) => {
    router.get('/tanah', { search: value }, {
        preserveState: true,
        preserveScroll: true,
    });
};



const openModal = () => {
    if (!hasBlok.value) {
        return;
    }
    form.reset();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const openDeleteModal = (item) => {
    deletingItem.value = item;
    showDeleteModal.value = true;
};
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingItem.value = null;
};

const openEditModal = (item) => {
    editingItem.value = item;
    editForm.no_urut = item.no_urut;
    editForm.nama_wajib_ipeda = item.nama_wajib_ipeda;
    editForm.tempat_tinggal = item.tempat_tinggal;
    editForm.nomor_persil = item.nomor_persil;
    editForm.blok_id = item.blok_id;
    editForm.jenis_tanah = item.jenis_tanah;
    editForm.luas_ha = item.luas_ha;
    editForm.luas_da = item.luas_da;
    editForm.ipeda_r = item.ipeda_r;
    editForm.ipeda_s = item.ipeda_s;
    editForm.sebab_perubahan = item.sebab_perubahan;
    editForm.tgl_perubahan = item.tgl_perubahan;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingItem.value = null;
    editForm.reset();
};
const blokOptions = computed(() => (props.blokList || []).map(b => ({ value: b.id, label: b.nama_blok })));

const goToImport = () => {
    if (!hasBlok.value) {
        return;
    }
    router.visit('/tanah/import');
};

const submitForm = () => {
    form.post('/tanah', {
        onSuccess: (page) => {

            toast.success(page.props.flash.message || 'Data berhasil disimpan');
            closeModal();
            form.reset();
        },
        onError: (errors) => {
            toast.error(errors?.error || 'Gagal menyimpan data');
            // handle error, show flash or validation
        },
    });
};

const updateForm = () => {
    editForm.put(`/tanah/${editingItem.value.id}`, {
        onSuccess: (page) => {


            closeEditModal();
            editForm.reset();
            toast.success(page.props.flash.message || 'Data berhasil diupdate');
        },
        onError: (errors) => {
            toast.error(errors?.error || 'Gagal mengupdate data');
        },
    });
};

const deleteItem = (id) => {
    errorMessage.value = '';
    router.delete(`/tanah/${id}`, {
        onSuccess: (page) => {


            closeDeleteModal();
            toast.success(page.props.flash.message || 'Data berhasil dihapus');
        },
        onError: (errors) => {
            toast.error(errors?.error || 'Gagal menghapus data');
        },
    });
};

const errors = ref({});

// Ubah watcher untuk menampilkan error jika <= 0
watch(() => form.luas_ha, (newVal) => {
    if (newVal < 0) {
        errors.value.luas_ha = 'Nilai harus lebih besar dari 0';
        form.luas_ha = '';
    } else {
        errors.value.luas_ha = '';
    }
});
watch(() => form.luas_da, (newVal) => {
    if (newVal < 0) {
        errors.value.luas_da = 'Nilai harus lebih besar dari 0';
        form.luas_da = '';
    } else {
        errors.value.luas_da = '';
    }
});
watch(() => form.ipeda_r, (newVal) => {
    if (newVal < 0) {
        errors.value.ipeda_r = 'Nilai harus lebih besar dari 0';
        form.ipeda_r = '';
    } else {
        errors.value.ipeda_r = '';
    }
});
watch(() => form.ipeda_s, (newVal) => {
    if (newVal < 0) {
        errors.value.ipeda_s = 'Nilai harus lebih besar dari 0';
        form.ipeda_s = '';
    } else {
        errors.value.ipeda_s = '';
    }
});

// Sama untuk editForm
watch(() => editForm.luas_ha, (newVal) => {
    if (newVal < 0) {
        errors.value.edit_luas_ha = 'Nilai harus lebih besar dari 0';
        editForm.luas_ha = '';
    } else {
        errors.value.edit_luas_ha = '';
    }
});
watch(() => editForm.luas_da, (newVal) => {
    if (newVal < 0) {
        errors.value.edit_luas_da = 'Nilai harus lebih besar dari 0';
        editForm.luas_da = '';
    } else {
        errors.value.edit_luas_da = '';
    }
});
watch(() => editForm.ipeda_r, (newVal) => {
    if (newVal < 0) {
        errors.value.edit_ipeda_r = 'Nilai harus lebih besar dari 0';
        editForm.ipeda_r = '';
    } else {
        errors.value.edit_ipeda_r = '';
    }
});
watch(() => editForm.ipeda_s, (newVal) => {
    if (newVal < 0) {
        errors.value.edit_ipeda_s = 'Nilai harus lebih besar dari 0';
        editForm.ipeda_s = '';
    } else {
        errors.value.edit_ipeda_s = '';
    }
});
</script>

<template>
    <AppLayout title="Manajemen Data Tanah">

        <Head title="Data Tanah" />

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Data Tanah</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola dan lihat catatan kepemilikan tanah di desa.</p>
        </div>

        <div v-if="!hasBlok" class="mb-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
            <p class="font-semibold">Peta blok belum tersedia.</p>
            <p class="text-sm">Unggah data peta blok terlebih dahulu sebelum menambah atau mengimpor data tanah.</p>
        </div>

        <!-- Search and Add Button -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="relative w-full md:w-96">
                    <SearchBar v-model="search" :placeholder="'Cari berdasarkan Jenis Tanah...'" @search="onSearch">
                        <template #icon>
                            <Search size="20" />
                        </template>
                    </SearchBar>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="goToImport" :disabled="!hasBlok"
                        class="flex items-center gap-2 px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <UploadCloudIcon size="20" />
                        Import Excel
                    </button>

                    <a class="flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                        :href="route('template.download', 'tanah')" download>
                        <Sheet size="20" />
                        Download Template Excel
                    </a>

                    <button @click="openModal" :disabled="!hasBlok"
                        class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">
                        <PlusIcon class="w-5 h-5" />
                        Tambah Data Tanah
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <Table :headers="headers" :rows="tanah?.data ?? []" key-field="id">
            <template #cell-no="{ index }">
                {{ (tanah.current_page - 1) * tanah.per_page + index + 1 }}
            </template>
            <template #cell-blok="{ row }">
                {{ row.blok?.nama_blok || '-' }}
            </template>

            <template #cell-persil="{ row }">
                {{ row.nomor_persil }}
            </template>


            <template #cell-luas_ha="{ row }">
                {{ row.luas_ha || '-' }}
            </template>
            <template #cell-luas_da="{ row }">
                {{ row.luas_da || '-' }}
            </template>

            <template #cell-aksi="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="openEditModal(row)"
                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Edit">
                        <SquarePen class="w-5 h-5" />
                    </button>
                    <button @click="openDeleteModal(row)"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors" title="Hapus">
                        <Trash2 class="w-5 h-5" />
                    </button>
                </div>
            </template>
        </Table>

        <!-- Pagination -->
        <Pagination :links="tanah?.links ?? []">
            <template #info>
                <span v-if="tanah?.from && tanah?.to && tanah?.total">
                    Showing {{ tanah.from }}-{{ tanah.to }} of {{ tanah.total }}
                </span>
                <span v-else>
                    Tidak ada data
                </span>
            </template>
        </Pagination>

        <!-- Add Modal -->
        <Modal :show="showModal" title="Tambah Data Tanah" max-width="4xl" @close="closeModal">
            <form @submit.prevent="submitForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput v-model="form.no_urut" label="No Urut" type="text"
                        placeholder="Silakan Input Nomor Urut disini..." />
                    <FormInput v-model="form.nama_wajib_ipeda" label="Nama Wajib IPEDA" type="text" required
                        placeholder="Silakan Input Wajib IPEDA disini..." />
                    <FormInput v-model="form.tempat_tinggal" label="Tempat Tinggal" type="text"
                        placeholder="Silakan Input Tempat Tinggal disini..." />
                    <FormInput v-model="form.nomor_persil" label="Nomor Persil" type="text" required
                        placeholder="Silakan Input Nomor Persil disini..." />
                    <FormInput v-model="form.blok_id" label="Blok" type="select" :options="blokOptions" required
                        placeholder="Silakan Pilih Tipe Blok disini.." />
                    <FormInput v-model="form.jenis_tanah" label="Jenis Tanah" type="select" :options="[
                        { value: 'basah', label: 'Tanah Basah' },
                        { value: 'kering', label: 'Tanah Kering' },
                    ]" required placeholder="Silakan Pilih Jenis Tanah disini..." />
                    <div>
                        <FormInput v-model="form.luas_ha" label="Luas HA" type="number"
                            placeholder="Silakan Input Luas Ipeda HA disini..." />
                        <InputError :message="errors.luas_ha" />
                    </div>
                    <div>
                        <FormInput v-model="form.luas_da" label="Luas DA" type="number"
                            placeholder="Silakan Input Luas Ipeda DA disini..." />
                        <InputError :message="errors.luas_da" />
                    </div>
                    <div>
                        <FormInput v-model="form.ipeda_r" label="IPEDA R" type="number"
                            placeholder="Silakan Input IPEDA R disini..." />
                        <InputError :message="errors.ipeda_r" />
                    </div>
                    <div>
                        <FormInput v-model="form.ipeda_s" label="IPEDA S" type="number"
                            placeholder="Silakan Input IPEDA S disini..." />
                        <InputError :message="errors.ipeda_s" />
                    </div>
                    <FormInput v-model="form.sebab_perubahan" label="Sebab Perubahan" type="text"
                        placeholder="Silakan Input Sebab Perubahan disini..." />
                    <FormInput v-model="form.tgl_perubahan" label="Tanggal Perubahan" type="date"
                        placeholder="Silakan Input Tanggal Perubahan disini..." />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submitForm" :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" title="Edit Data Tanah" max-width="4xl" @close="closeEditModal">
            <form @submit.prevent="updateForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput v-model="editForm.no_urut" label="No Urut" type="text" />
                    <FormInput v-model="editForm.nama_wajib_ipeda" label="Nama Wajib IPEDA" type="text" required />
                    <FormInput v-model="editForm.tempat_tinggal" label="Tempat Tinggal" type="text" />
                    <FormInput v-model="editForm.nomor_persil" label="Nomor Persil" type="text" required />
                    <FormInput v-model="editForm.blok_id" label="Blok" type="select" :options="blokOptions" required />
                    <FormInput v-model="editForm.jenis_tanah" label="Jenis Tanah" type="select" :options="[
                        { value: 'basah', label: 'Tanah Basah' },
                        { value: 'kering', label: 'Tanah Kering' },
                    ]" required />
                    <div>

                        <FormInput v-model="editForm.luas_ha" label="Luas HA" type="number" />
                        <InputError :message="errors.edit_luas_ha" />
                    </div>
                    <div>
                        <FormInput v-model="editForm.luas_da" label="Luas DA" type="number" />
                        <InputError :message="errors.edit_luas_da" />
                    </div>
                    <div>
                        <FormInput v-model="editForm.ipeda_r" label="IPEDA R" type="number" />
                        <InputError :message="errors.edit_ipeda_r" />
                    </div>
                    <div>
                        <FormInput v-model="editForm.ipeda_s" label="IPEDA S" type="number" />
                        <InputError :message="errors.edit_ipeda_s" />
                    </div>
                    <FormInput v-model="editForm.sebab_perubahan" label="Sebab Perubahan" type="text" />
                    <FormInput v-model="editForm.tgl_perubahan" label="Tanggal Perubahan" type="date" />

                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeEditModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="updateForm" :disabled="editForm.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                        {{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" title="Konfirmasi Hapus Data" max-width="md" @close="closeDeleteModal">
            <p>Apakah Anda yakin ingin menghapus data tanah milik <strong>{{ deletingItem?.nama_wajib_ipeda }}</strong>?
            </p>
            <InputError :message="errorMessage" class="mt-4 mb-2" />
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" @click="closeDeleteModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="deleteItem(deletingItem.id)" :disabled="!deletingItem"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Hapus
                    </button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
