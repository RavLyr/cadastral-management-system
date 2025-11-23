<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    row: Object,
    mapBloks: Array,
});

const form = useForm({
    nop: props.row.nop,
    objek_pajak_jalan_dusun_op: props.row.objek_pajak_jalan_dusun_op,
    objek_pajak_rt: props.row.objek_pajak_rt,
    objek_pajak_rw: props.row.objek_pajak_rw,
    objek_pajak_desa: props.row.objek_pajak_desa,
    subjek_pajak_nama_wajib_pajak: props.row.subjek_pajak_nama_wajib_pajak,
    subjek_pajak_jalan_dusun: props.row.subjek_pajak_jalan_dusun,
    subjek_pajak_rt: props.row.subjek_pajak_rt,
    subjek_pajak_rw: props.row.subjek_pajak_rw,
    subjek_pajak_desa_kel: props.row.subjek_pajak_desa_kel,
    subjek_pajak_kabupaten_kota: props.row.subjek_pajak_kabupaten_kota,
    bumi: props.row.bumi,
    bng: props.row.bng,
    jns_bumi: props.row.jns_bumi,
    usulan_pembetulan: props.row.usulan_pembetulan,
    blok: props.row.blok,
    no_urut: props.row.no_urut,
    map_blok_id: props.row.map_blok_id ?? null,
});


const submit = () => {
    form.put(route('sismiop.update', props.row.id), {
        onSuccess: (e) => {
            toast.success(e.props?.flash?.message || 'Data berhasil diperbarui');
        },
        onError: (errors) => {
            if (errors && typeof errors === 'object') {
                Object.entries(errors).forEach(([field, msgs]) => {
                    toast.error(msgs);
                });
            }
        },
    });
};

</script>

<template>
    <AppLayout title="Edit Data SISMIOP">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Data {{ props.row.subjek_pajak_nama_wajib_pajak }}</h1>
        </div>
        <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormInput v-model="form.nop" label="NOP" type="text" disabled required />
                    <FormInput v-model="form.objek_pajak_jalan_dusun_op" label="Jalan/Dusun OP" type="text" />
                    <FormInput v-model="form.objek_pajak_rt" label="RT OP" type="text" />
                    <FormInput v-model="form.objek_pajak_rw" label="RW OP" type="text" />
                    <FormInput v-model="form.objek_pajak_desa" label="Desa OP" type="text" />
                    <FormInput v-model="form.subjek_pajak_nama_wajib_pajak" label="Nama Wajib Pajak" type="text"
                        required />
                    <FormInput v-model="form.subjek_pajak_jalan_dusun" label="Jalan/Dusun WP" type="text" />
                    <FormInput v-model="form.subjek_pajak_rt" label="RT WP" type="text" />
                    <FormInput v-model="form.subjek_pajak_rw" label="RW WP" type="text" />
                    <FormInput v-model="form.subjek_pajak_desa_kel" label="Desa/Kel WP" type="text" />
                    <FormInput v-model="form.subjek_pajak_kabupaten_kota" label="Kabupaten/Kota WP" type="text" />
                    <FormInput v-model="form.bumi" label="Bumi" type="number" />
                    <FormInput v-model="form.bng" label="Bangunan" type="number" />
                    <FormInput v-model="form.jns_bumi" label="Jenis Bumi" type="text" />
                    <FormInput v-model="form.usulan_pembetulan" label="Usulan Pembetulan" type="text" />
                    <FormInput v-model="form.blok" label="Blok" type="text" />
                    <FormInput v-model="form.no_urut" label="Nomor Urut" type="text" />
                    <FormInput v-model="form.map_blok_id" label="Peta Blok" type="select"
                        :options="mapBloks.map(b => ({ value: b.id, label: b.nama_blok }))" />
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
