<script setup>
defineProps({
    histories: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const valueOrDash = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const number = Number(value);
    if (Number.isNaN(number)) {
        return value;
    }

    return Number(number.toFixed(4)).toString();
};
</script>

<template>
    <div class="rounded-lg border border-gray-200 p-3 space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h4 class="text-sm font-semibold text-gray-900">Riwayat Perubahan</h4>
            <span v-if="histories.length" class="text-xs text-gray-500">{{ histories.length }} catatan</span>
        </div>

        <div v-if="histories.length" class="max-h-[280px] space-y-3 overflow-y-auto pr-1">
            <div
                v-for="history in histories"
                :key="history.id"
                class="rounded-md border border-gray-100 bg-gray-50 p-3 text-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-medium text-gray-900">{{ history.jenis_perubahan || '-' }}</span>
                    <span class="text-xs text-gray-500">{{ formatDate(history.tanggal_perubahan) }}</span>
                </div>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <div><span class="text-gray-500">Luas awal (HA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_awal) }}</span></div>
                    <div><span class="text-gray-500">Luas awal (DA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_awal_da) }}</span></div>
                    <div><span class="text-gray-500">Luas berubah (HA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_berubah) }}</span></div>
                    <div><span class="text-gray-500">Luas berubah (DA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_berubah_da) }}</span></div>
                    <div><span class="text-gray-500">Luas aktif akhir (HA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_sisa) }}</span></div>
                    <div><span class="text-gray-500">Luas aktif akhir (DA):</span> <span class="font-medium text-gray-900">{{ valueOrDash(history.luas_sisa_da) }}</span></div>
                    <div><span class="text-gray-500">Pemilik baru:</span> <span class="font-medium text-gray-900">{{ history.pemilik_baru || '-' }}</span></div>
                    <div><span class="text-gray-500">Keterangan:</span> <span class="font-medium text-gray-900">{{ history.keterangan || '-' }}</span></div>
                </div>
            </div>
        </div>

        <div v-else class="text-sm text-gray-500">
            Belum ada riwayat perubahan.
        </div>
    </div>
</template>
