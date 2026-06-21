<script setup>
import { computed, reactive, watch } from 'vue';
import { toast } from 'vue-sonner';
import Modal from '@/Components/Modal.vue';
import FormInput from '@/Components/FormInput.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    tanah: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const jenisOptions = [
    { value: 'Dijual sebagian', label: 'Dijual sebagian' },
    { value: 'Koreksi Data', label: 'Koreksi Data' },
    { value: 'Ganti Pemilik', label: 'Ganti Pemilik' },
    { value: 'Waris', label: 'Waris' },
    { value: 'Hibah', label: 'Hibah' },
];

const jenisTanahOptions = [
    { value: 'basah', label: 'Basah' },
    { value: 'kering', label: 'Kering' },
];

const form = reactive({
    jenis_perubahan: 'Dijual sebagian',
    tanggal_perubahan: '',
    luas_awal: '',
    luas_awal_da: '',
    luas_berubah: '',
    luas_berubah_da: '',
    luas_sisa: '',
    luas_sisa_da: '',
    pemilik_lama: '',
    pemilik_baru: '',
    keterangan: '',
    child_nop: '',
    child_nama_wajib_ipeda: '',
    child_tempat_tinggal: '',
    child_jenis_tanah: '',
    child_blok_id: '',
    child_blok: '',
    child_nomor_persil: '',
    child_kelas_desa: '',
    child_luas_ha: '',
    child_luas_da: '',
    child_ipeda_r: '',
    child_ipeda_s: '',
    child_sebab_perubahan: '',
    child_tgl_perubahan: '',
});

const errors = reactive({});
const state = reactive({
    processing: false,
    step: 'form',
});

const numberOrNull = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);
    return Number.isNaN(number) ? null : number;
};

const formatNumber = (value) => {
    const number = numberOrNull(value);
    if (number === null) {
        return '';
    }

    return Number(number.toFixed(4)).toString();
};

const displayValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return value;
};

const isPartialSale = computed(() => form.jenis_perubahan.trim().toLowerCase() === 'dijual sebagian');
const isOwnerChange = computed(() => form.jenis_perubahan.trim().toLowerCase() === 'ganti pemilik');
const isCorrection = computed(() => form.jenis_perubahan.trim().toLowerCase() === 'koreksi data');
const isInheritance = computed(() => form.jenis_perubahan.trim().toLowerCase() === 'waris');
const isGrant = computed(() => form.jenis_perubahan.trim().toLowerCase() === 'hibah');

const showOwnerFields = computed(() => {
    return isPartialSale.value || isOwnerChange.value || isInheritance.value || isGrant.value;
});

const pemilikLamaLabel = computed(() => {
    if (isGrant.value) {
        return 'Pemberi';
    }
    return 'Pemilik Lama';
});

const pemilikBaruLabel = computed(() => {
    if (isInheritance.value) {
        return 'Ahli Waris / Pemilik Baru';
    }
    if (isGrant.value) {
        return 'Penerima';
    }
    return 'Pemilik Baru';
});

const basisHa = computed(() => numberOrNull(props.tanah?.luas_sisa_ha ?? props.tanah?.luas_ha));
const basisDa = computed(() => numberOrNull(props.tanah?.luas_sisa_da ?? props.tanah?.luas_da));
const parentNop = computed(() => props.tanah?.nop_raw || props.tanah?.nop || '');
const parentPersil = computed(() => props.tanah?.nomor_persil || '');
const defaultChildReason = computed(() => `Hasil pembagian dari tanah ${props.tanah?.nama_wajib_ipeda || '-'} / NOP ${parentNop.value || '-'}`);

const syncChildFields = () => {
    form.child_nop = parentNop.value;
    form.child_nama_wajib_ipeda = form.pemilik_baru;
    form.child_jenis_tanah = props.tanah?.jenis_tanah || '';
    form.child_blok_id = props.tanah?.blok_id || '';
    form.child_blok = props.tanah?.blok || '';
    form.child_kelas_desa = '';
    form.child_luas_ha = form.luas_berubah;
    form.child_luas_da = form.luas_berubah_da;
    form.child_sebab_perubahan = defaultChildReason.value;
    form.child_tgl_perubahan = form.tanggal_perubahan;
};

const recalculateSisa = () => {
    if (!isPartialSale.value) {
        return;
    }

    const changedHa = numberOrNull(form.luas_berubah) ?? 0;
    const changedDa = numberOrNull(form.luas_berubah_da) ?? 0;

    form.luas_sisa = basisHa.value === null ? '' : formatNumber(basisHa.value - changedHa);
    form.luas_sisa_da = basisDa.value === null ? '' : formatNumber(basisDa.value - changedDa);
    syncChildFields();
};

watch(() => [form.jenis_perubahan, form.luas_berubah, form.luas_berubah_da], () => {
    recalculateSisa();
    if (isPartialSale.value) {
        syncChildFields();
    }
});

watch(() => [form.pemilik_baru, form.tanggal_perubahan], () => {
    if (isPartialSale.value) {
        syncChildFields();
    }
});

watch(() => form.jenis_perubahan, (newValue) => {
    Object.keys(errors).forEach((key) => delete errors[key]);
    if (newValue.trim().toLowerCase() === 'koreksi data') {
        form.pemilik_lama = '';
        form.pemilik_baru = '';
    } else {
        form.pemilik_lama = props.tanah?.nama_wajib_ipeda || '';
    }
});

const resetForm = () => {
    state.step = 'form';
    form.jenis_perubahan = 'Dijual sebagian';
    form.tanggal_perubahan = '';
    form.luas_awal = formatNumber(basisHa.value);
    form.luas_awal_da = formatNumber(basisDa.value);
    form.luas_berubah = '';
    form.luas_berubah_da = '';
    form.luas_sisa = formatNumber(basisHa.value);
    form.luas_sisa_da = formatNumber(basisDa.value);
    form.pemilik_lama = props.tanah?.nama_wajib_ipeda || '';
    form.pemilik_baru = '';
    form.keterangan = '';
    form.child_tempat_tinggal = '';
    form.child_nomor_persil = '';
    form.child_ipeda_r = '';
    form.child_ipeda_s = '';
    syncChildFields();
    Object.keys(errors).forEach((key) => delete errors[key]);
};

watch(() => props.show, (show) => {
    if (show) {
        resetForm();
    }
});

const close = () => {
    if (!state.processing) {
        emit('close');
    }
};

const setError = (key, message) => {
    errors[key] = [message];
};

const validateFrontend = () => {
    Object.keys(errors).forEach((key) => delete errors[key]);

    if (!form.jenis_perubahan.trim()) {
        setError('jenis_perubahan', 'Jenis perubahan wajib diisi.');
        return false;
    }

    if (!form.tanggal_perubahan) {
        setError('tanggal_perubahan', 'Tanggal perubahan wajib diisi.');
        return false;
    }

    if (isPartialSale.value && !form.pemilik_baru.trim()) {
        setError('pemilik_baru', 'Pemilik baru wajib diisi.');
        return false;
    }

    if (isOwnerChange.value && !form.pemilik_baru.trim()) {
        setError('pemilik_baru', 'Pemilik baru wajib diisi.');
        return false;
    }

    if (isInheritance.value && !form.pemilik_baru.trim()) {
        setError('pemilik_baru', 'Ahli waris/pemilik baru wajib diisi.');
        return false;
    }

    if (isGrant.value && !form.pemilik_baru.trim()) {
        setError('pemilik_baru', 'Penerima wajib diisi.');
        return false;
    }

    if (isGrant.value && !form.pemilik_lama.trim()) {
        setError('pemilik_lama', 'Pemberi wajib diisi.');
        return false;
    }

    if (!isPartialSale.value) {
        return true;
    }

    const changedHa = numberOrNull(form.luas_berubah) ?? 0;
    const changedDa = numberOrNull(form.luas_berubah_da) ?? 0;
    const sisaHa = numberOrNull(form.luas_sisa);
    const sisaDa = numberOrNull(form.luas_sisa_da);

    if (changedHa <= 0 && changedDa <= 0) {
        setError('luas_berubah', 'Isi luas dijual HA atau DA lebih besar dari 0.');
        return false;
    }

    if (basisHa.value !== null && changedHa > basisHa.value) {
        setError('luas_berubah', 'Luas dijual (HA) tidak boleh lebih besar dari luas sisa saat ini.');
        return false;
    }

    if (basisDa.value !== null && changedDa > basisDa.value) {
        setError('luas_berubah_da', 'Luas dijual (DA) tidak boleh lebih besar dari luas sisa saat ini.');
        return false;
    }

    if (sisaHa !== null && sisaHa < 0) {
        setError('luas_sisa', 'Luas aktif baru (HA) tidak boleh minus.');
        return false;
    }

    if (sisaDa !== null && sisaDa < 0) {
        setError('luas_sisa_da', 'Luas aktif baru (DA) tidak boleh minus.');
        return false;
    }

    return true;
};

const prepareConfirmation = () => {
    if (!props.tanah?.id) {
        toast.error('Data tanah tidak tersedia.');
        return;
    }

    if (isPartialSale.value) {
        syncChildFields();
    }

    if (!validateFrontend()) {
        toast.error('Periksa kembali isian perubahan tanah.');
        return;
    }

    state.step = 'confirm';
};

const submit = async () => {
    if (!validateFrontend()) {
        state.step = 'form';
        toast.error('Periksa kembali isian perubahan tanah.');
        return;
    }

    state.processing = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const payload = {
            jenis_perubahan: form.jenis_perubahan,
            tanggal_perubahan: form.tanggal_perubahan,
            keterangan: form.keterangan,
        };

        if (isPartialSale.value) {
            payload.luas_awal = form.luas_awal;
            payload.luas_awal_da = form.luas_awal_da;
            payload.luas_berubah = form.luas_berubah;
            payload.luas_berubah_da = form.luas_berubah_da;
            payload.luas_sisa = form.luas_sisa;
            payload.luas_sisa_da = form.luas_sisa_da;
            payload.pemilik_lama = form.pemilik_lama;
            payload.pemilik_baru = form.pemilik_baru;

            payload.child_nop = form.child_nop;
            payload.child_nama_wajib_ipeda = form.child_nama_wajib_ipeda;
            payload.child_tempat_tinggal = form.child_tempat_tinggal;
            payload.child_jenis_tanah = form.child_jenis_tanah;
            payload.child_nomor_persil = form.child_nomor_persil;
            payload.child_luas_ha = form.child_luas_ha;
            payload.child_luas_da = form.child_luas_da;
            payload.child_ipeda_r = form.child_ipeda_r;
            payload.child_ipeda_s = form.child_ipeda_s;
            payload.child_sebab_perubahan = form.child_sebab_perubahan;
            payload.child_tgl_perubahan = form.child_tgl_perubahan;
        } else if (isOwnerChange.value || isInheritance.value || isGrant.value) {
            payload.pemilik_lama = form.pemilik_lama;
            payload.pemilik_baru = form.pemilik_baru;
        }

        const response = await window.axios.post(`/tanah/${props.tanah.id}/histories`, payload, {
            headers: {
                Accept: 'application/json',
            },
        });

        toast.success(response.data?.message || 'Riwayat perubahan berhasil dicatat.');
        emit('saved', response.data);
        emit('close');
    } catch (error) {
        const validationErrors = error.response?.data?.errors || {};
        Object.assign(errors, validationErrors);
        state.step = 'form';
        toast.error(error.response?.data?.message || 'Gagal mencatat riwayat perubahan.');
    } finally {
        state.processing = false;
    }
};
</script>

<template>
    <Modal :show="show" title="Catat Perubahan" max-width="4xl" @close="close">
        <form v-if="state.step === 'form'" class="space-y-5" @submit.prevent="prepareConfirmation">
            <input type="hidden" :value="tanah?.id || ''">

            <section class="space-y-4 rounded-lg border border-gray-200 p-4">
                <h4 class="text-sm font-semibold text-gray-900">Data Perubahan</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput
                        v-model="form.jenis_perubahan"
                        label="Jenis Perubahan"
                        type="select"
                        :options="jenisOptions"
                        required
                        :error="errors.jenis_perubahan?.[0]"
                    />
                    <FormInput
                        v-model="form.tanggal_perubahan"
                        label="Tanggal Perubahan"
                        type="date"
                        required
                        :error="errors.tanggal_perubahan?.[0]"
                    />
                    
                    <!-- Helper Text -->
                    <div class="md:col-span-2 text-xs text-blue-700 bg-blue-50/80 p-3 rounded-lg border border-blue-100 leading-relaxed">
                        Gunakan Dijual Sebagian jika terjadi pembagian bidang tanah. Jenis perubahan lainnya hanya mencatat perubahan administratif.
                    </div>

                    <FormInput
                        v-if="showOwnerFields"
                        v-model="form.pemilik_lama"
                        :label="pemilikLamaLabel"
                        type="text"
                        :required="isGrant"
                        :error="errors.pemilik_lama?.[0]"
                    />
                    <FormInput
                        v-if="showOwnerFields"
                        v-model="form.pemilik_baru"
                        :label="pemilikBaruLabel"
                        type="text"
                        :required="isPartialSale || isOwnerChange || isInheritance || isGrant"
                        :error="errors.pemilik_baru?.[0]"
                    />
                    <FormInput
                        v-model="form.keterangan"
                        label="Keterangan"
                        type="textarea"
                        class="md:col-span-2"
                        :error="errors.keterangan?.[0]"
                    />
                </div>
            </section>

            <section v-if="isPartialSale" class="space-y-4 rounded-lg border border-gray-200 p-4">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Informasi Luas</h4>
                    <p class="mt-1 text-xs text-gray-500">Basis pembagian memakai luas sisa saat ini, bukan luas awal bidang.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput v-model="form.luas_awal" label="Luas Aktif Saat Ini / Basis (HA)" type="number" disabled />
                    <FormInput v-model="form.luas_awal_da" label="Luas Aktif Saat Ini / Basis (DA)" type="number" disabled />
                    <FormInput
                        v-model="form.luas_berubah"
                        label="Luas Dijual/Berubah (HA)"
                        type="number"
                        :required="isPartialSale"
                        :error="errors.luas_berubah?.[0]"
                    />
                    <FormInput
                        v-model="form.luas_berubah_da"
                        label="Luas Dijual/Berubah (DA)"
                        type="number"
                        :error="errors.luas_berubah_da?.[0]"
                    />
                    <FormInput
                        v-model="form.luas_sisa"
                        label="Luas Sisa Baru (HA)"
                        type="number"
                        disabled
                        :error="errors.luas_sisa?.[0]"
                    />
                    <FormInput
                        v-model="form.luas_sisa_da"
                        label="Luas Sisa Baru (DA)"
                        type="number"
                        disabled
                        :error="errors.luas_sisa_da?.[0]"
                    />
                </div>
            </section>

            <section v-if="isPartialSale" class="space-y-4 rounded-lg border border-blue-200 bg-blue-50/40 p-4">
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Data Tanah Baru Hasil Pembagian</h4>
                    <p class="mt-1 text-xs text-gray-600">Untuk Dijual sebagian, data hasil pembagian otomatis dibuat sebagai data administrasi. Polygon GIS tidak diubah.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput v-model="form.child_nop" label="NOP" type="text" disabled />
                    <FormInput v-model="form.child_nama_wajib_ipeda" label="Nama Wajib IPEDA" type="text" disabled />
                    <FormInput v-model="form.child_tempat_tinggal" label="Tempat Tinggal" type="text" :error="errors.child_tempat_tinggal?.[0]" />
                    <FormInput v-model="form.child_jenis_tanah" label="Jenis Tanah" type="select" :options="jenisTanahOptions" disabled :error="errors.child_jenis_tanah?.[0]" />
                    <FormInput v-model="form.child_blok" label="Blok" type="text" disabled />
                    <FormInput
                        v-model="form.child_nomor_persil"
                        label="Nomor Persil"
                        type="text"
                        :placeholder="parentPersil ? `Kosongkan untuk auto dari ${parentPersil}` : 'Kosongkan untuk dibuat otomatis'"
                        :error="errors.child_nomor_persil?.[0]"
                    />
                    <FormInput v-model="form.child_luas_ha" label="Luas HA" type="number" disabled />
                    <FormInput v-model="form.child_luas_da" label="Luas DA" type="number" disabled />
                    <FormInput v-model="form.child_ipeda_r" label="IPEDA R" type="number" :error="errors.child_ipeda_r?.[0]" />
                    <FormInput v-model="form.child_ipeda_s" label="IPEDA S" type="number" :error="errors.child_ipeda_s?.[0]" />
                    <FormInput v-model="form.child_tgl_perubahan" label="Tanggal Perubahan" disabled type="date" />
                    <FormInput
                        v-model="form.child_sebab_perubahan"
                        label="Sebab Perubahan"
                        type="textarea"
                        class="md:col-span-2"
                        :error="errors.child_sebab_perubahan?.[0]"
                    />
                </div>
            </section>
        </form>

        <div v-else class="space-y-5">
            <section class="rounded-lg border border-gray-200 p-4">
                <h4 class="text-sm font-semibold text-gray-900">Konfirmasi Perubahan</h4>
                <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">Jenis Perubahan</dt><dd class="font-medium text-gray-900">{{ displayValue(form.jenis_perubahan) }}</dd></div>
                    <div v-if="showOwnerFields"><dt class="text-gray-500">{{ pemilikLamaLabel }}</dt><dd class="font-medium text-gray-900">{{ displayValue(form.pemilik_lama || tanah?.nama_wajib_ipeda) }}</dd></div>
                    <div v-if="showOwnerFields"><dt class="text-gray-500">{{ pemilikBaruLabel }}</dt><dd class="font-medium text-gray-900">{{ displayValue(form.pemilik_baru) }}</dd></div>
                    <template v-if="isPartialSale">
                        <div><dt class="text-gray-500">Luas Aktif Saat Ini (HA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_awal) }}</dd></div>
                        <div><dt class="text-gray-500">Luas Aktif Saat Ini (DA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_awal_da) }}</dd></div>
                        <div><dt class="text-gray-500">Luas Dijual/Berubah (HA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_berubah) }}</dd></div>
                        <div><dt class="text-gray-500">Luas Dijual/Berubah (DA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_berubah_da) }}</dd></div>
                        <div><dt class="text-gray-500">Luas Sisa Setelah Perubahan (HA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_sisa) }}</dd></div>
                        <div><dt class="text-gray-500">Luas Sisa Setelah Perubahan (DA)</dt><dd class="font-medium text-gray-900">{{ displayValue(form.luas_sisa_da) }}</dd></div>
                    </template>
                    <div><dt class="text-gray-500">Tanggal Perubahan</dt><dd class="font-medium text-gray-900">{{ displayValue(form.tanggal_perubahan) }}</dd></div>
                    <div class="md:col-span-2"><dt class="text-gray-500">Keterangan</dt><dd class="font-medium text-gray-900">{{ displayValue(form.keterangan) }}</dd></div>
                </dl>
            </section>

            <section v-if="isPartialSale" class="space-y-3 rounded-lg border border-blue-200 bg-blue-50/40 p-4">
                <h4 class="text-sm font-semibold text-gray-900">Data Hasil Pembagian yang Akan Dibuat</h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500">NOP</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_nop) }}</dd></div>
                    <div><dt class="text-gray-500">Nama Wajib IPEDA</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_nama_wajib_ipeda) }}</dd></div>
                    <div><dt class="text-gray-500">Jenis Tanah</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_jenis_tanah) }}</dd></div>
                    <div><dt class="text-gray-500">Blok</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_blok) }}</dd></div>
                    <div><dt class="text-gray-500">Nomor Persil</dt><dd class="font-medium text-gray-900">{{ form.child_nomor_persil ? form.child_nomor_persil : 'Otomatis dari sistem' }}</dd></div>
                    <div><dt class="text-gray-500">Luas HA</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_luas_ha) }}</dd></div>
                    <div><dt class="text-gray-500">Luas DA</dt><dd class="font-medium text-gray-900">{{ displayValue(form.child_luas_da) }}</dd></div>
                </dl>
                <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700">
                    <li>Sistem menyimpan riwayat perubahan.</li>
                    <li>Sistem mengurangi luas aktif data awal.</li>
                    <li>Sistem membuat data tanah baru hasil pembagian.</li>
                    <li>Polygon GIS tidak diubah.</li>
                </ul>
            </section>
        </div>

        <template #footer>
            <div v-if="state.step === 'form'" class="flex justify-end gap-3">
                <button
                    type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    @click="close"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="state.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                    @click="prepareConfirmation"
                >
                    Simpan
                </button>
            </div>
            <div v-else class="flex justify-end gap-3">
                <button
                    type="button"
                    :disabled="state.processing"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                    @click="state.step = 'form'"
                >
                    Kembali Edit
                </button>
                <button
                    type="button"
                    :disabled="state.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                    @click="submit"
                >
                    {{ state.processing ? 'Menyimpan...' : 'Ya, Simpan Perubahan' }}
                </button>
            </div>
        </template>
    </Modal>
</template>
