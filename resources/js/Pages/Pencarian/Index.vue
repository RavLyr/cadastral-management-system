<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { FileSearch, MapPinned } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import Pagination from '@/Components/Pagination.vue';
import TanahHistoryList from '@/Components/TanahHistoryList.vue';
import TanahHistoryModal from '@/Components/TanahHistoryModal.vue';

const props = defineProps({
    results: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'NAMA', key: 'nama_wajib_ipeda' },
    { label: 'NOP', key: 'nop' },
    { label: 'PERSIL', key: 'nomor_persil' },
    { label: 'BLOK', key: 'blok' },
    { label: 'LUAS (M2)', key: 'luas_m2' },
    { label: 'AKSI', key: 'actions' },
];

const search = ref(props.filters.search || '');
const selectedNop = ref(null);
const selectedNopRaw = ref(null);
const selectedTanahId = ref(null);
const selectedAdmin = ref(null);
const selectedSismiop = ref(null);
const selectedProperties = ref(null);
const selectedStatus = ref('');
const tanahRecords = ref([]);
const showHistoryModal = ref(false);
const historyTarget = ref(null);
const mapReady = ref(false);
const loadingBidang = ref(false);
const loadingRecords = ref(false);
const loadingDetail = ref(false);
const map = ref(null);
const allBidangLayer = ref(null);
const selectedLayer = ref(null);
const nopStatusMap = ref({});
const basemapId = ref('carto');
const baseLayer = ref(null);
let searchTimeout = null;

const tanahStyle = {
    color: '#2563eb',
    weight: 1,
    fillColor: '#3b82f6',
    fillOpacity: 0.35,
};

const sismiopStyle = {
    color: '#65a30d',
    weight: 1,
    fillColor: '#a3e635',
    fillOpacity: 0.3,
};

const emptyStyle = {
    color: '#ea580c',
    weight: 1,
    fillColor: '#f97316',
    fillOpacity: 0.20,
};

const highlightStyle = {
    color: '#dc2626',
    weight: 3,
    fillColor: '#ef4444',
    fillOpacity: 0.45,
};

const basemaps = [
    {
        id: 'osm',
        label: 'OpenStreetMap',
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors',
    },
    {
        id: 'carto',
        label: 'Carto Light',
        url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    },
    {
        id: 'esri',
        label: 'Esri Satellite',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        attribution: 'Tiles &copy; Esri',
    },
    {
        id: 'none',
        label: 'No Basemap (Offline)',
        url: null,
        attribution: '',
    },
];

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

onMounted(async () => {
    initMap();
    await loadNopStatuses();
    await loadAllBidang();
});

const initMap = () => {
    map.value = L.map('pencarian-map').setView([-7.1002, 110.7002], 14);
    setBasemap(basemapId.value);
    mapReady.value = true;
};

const setBasemap = (id) => {
    const selected = basemaps.find((item) => item.id === id) || basemaps[0];
    basemapId.value = selected.id;

    if (baseLayer.value) {
        map.value.removeLayer(baseLayer.value);
        baseLayer.value = null;
    }

    if (!selected.url) {
        return;
    }

    baseLayer.value = L.tileLayer(selected.url, {
        attribution: selected.attribution,
        maxZoom: 20,
    });

    baseLayer.value.addTo(map.value);
};

const normalizeNop = (value) => {
    if (!value) {
        return null;
    }

    const digits = String(value).trim().replace(/\D+/g, '');
    return digits || null;
};

const loadNopStatuses = async () => {
    try {
        const res = await fetch('/api/gis/nop-status');
        if (!res.ok) {
            return;
        }
        const data = await res.json();
        nopStatusMap.value = data.status || {};
    } catch (error) {
        nopStatusMap.value = {};
    }
};

const getBaseStyle = (nop) => {
    const normalized = normalizeNop(nop);
    const status = normalized ? nopStatusMap.value[normalized] : 'empty';

    if (status === 'tanah') {
        return { ...tanahStyle };
    }

    if (status === 'sismiop') {
        return { ...sismiopStyle };
    }

    return { ...emptyStyle };
};

const loadAllBidang = async () => {
    if (!mapReady.value) {
        return;
    }

    loadingBidang.value = true;

    try {
        const res = await fetch('/api/gis/bidang');
        if (!res.ok) {
            toast.error('Gagal memuat polygon bidang tanah.');
            return;
        }

        const data = await res.json();

        if (allBidangLayer.value) {
            map.value.removeLayer(allBidangLayer.value);
        }

        allBidangLayer.value = L.geoJSON(data, {
            style: (feature) => getBaseStyle(feature?.properties?.nop),
            onEachFeature: (feature, layer) => {
                const nop = feature?.properties?.nop;
                layer.on('click', async () => {
                    selectedProperties.value = feature?.properties || null;
                    await selectBidang(nop, layer);
                });
            },
        }).addTo(map.value);

        const bounds = allBidangLayer.value.getBounds();
        if (bounds.isValid()) {
            map.value.fitBounds(bounds.pad(0.08));
        }
    } catch (error) {
        toast.error('Terjadi kesalahan saat memuat semua polygon.');
    } finally {
        loadingBidang.value = false;
    }
};

const findLayerByNop = (nop) => {
    let found = null;
    const needle = normalizeNop(nop);

    if (!allBidangLayer.value || !needle) {
        return null;
    }

    allBidangLayer.value.eachLayer((layer) => {
        const layerNop = normalizeNop(layer?.feature?.properties?.nop);
        if (layerNop && layerNop === needle) {
            found = layer;
        }
    });

    return found;
};

const resetSelectedLayer = () => {
    if (selectedLayer.value && selectedLayer.value.setStyle) {
        const nop = selectedLayer.value?.feature?.properties?.nop;
        selectedLayer.value.setStyle(getBaseStyle(nop));
    }
};

const markSelectedLayer = (layer) => {
    if (!layer || !layer.setStyle) {
        return;
    }

    resetSelectedLayer();
    selectedLayer.value = layer;
    layer.setStyle(highlightStyle);

    if (layer.bringToFront) {
        layer.bringToFront();
    }
};

const focusLayer = (layer) => {
    if (!layer) {
        return;
    }

    selectedProperties.value = layer?.feature?.properties || selectedProperties.value;
    markSelectedLayer(layer);

    const bounds = layer.getBounds?.();
    if (bounds?.isValid?.()) {
        map.value.fitBounds(bounds.pad(0.2));
    }
};

const fetchTanahDetail = async (id) => {
    if (!id) {
        selectedTanahId.value = null;
        selectedAdmin.value = null;
        return null;
    }

    loadingDetail.value = true;

    try {
        const res = await fetch(`/api/tanah/${encodeURIComponent(id)}/detail`);
        if (!res.ok) {
            toast.error('Gagal memuat detail data tanah.');
            return null;
        }

        const detail = await res.json();
        selectedTanahId.value = detail.id;
        selectedAdmin.value = detail;
        return detail;
    } catch (error) {
        toast.error('Terjadi kesalahan saat memuat detail data tanah.');
        return null;
    } finally {
        loadingDetail.value = false;
    }
};

const fetchTanahRecordsByNop = async (nop) => {
    loadingRecords.value = true;

    try {
        const res = await fetch(`/api/tanah/by-nop/${encodeURIComponent(nop)}/records`);
        if (!res.ok) {
            tanahRecords.value = [];
            return [];
        }

        const data = await res.json();
        tanahRecords.value = data.records || [];
        return tanahRecords.value;
    } catch (error) {
        tanahRecords.value = [];
        return [];
    } finally {
        loadingRecords.value = false;
    }
};

const fetchSismiopData = async (nop) => {
    try {
        const res = await fetch(`/api/sismiop/by-nop/${encodeURIComponent(nop)}`);

        if (!res.ok) {
            selectedSismiop.value = null;
            return null;
        }

        const row = await res.json();
        selectedSismiop.value = row;
        return row;
    } catch (error) {
        selectedSismiop.value = null;
        return null;
    }
};

const buildPopupHtml = (nop, records, sismiop) => {
    if (records.length > 1) {
        return `
            <div>
                <strong>Tanah dimiliki beberapa pihak</strong><br/>
                NOP: ${formatNopDisplay(nop)}<br/>
                Ada ${records.length} data administrasi pada bidang ini.<br/>
                Pilih data tanah dari panel informasi.
            </div>
        `;
    }

    if (records.length === 1) {
        const record = records[0];
        return `
            <div>
                <strong>${record.nama_wajib_ipeda || '-'}</strong><br/>
                NOP: ${formatNopDisplay(record.nop_raw || record.nop)}<br/>
                Status: ${record.status_label || '-'}<br/>
                Persil: ${record.nomor_persil || '-'}<br/>
                Luas aktif: ${formatLuasHaDa(record.luas_sisa_ha ?? record.luas_ha, record.luas_sisa_da ?? record.luas_da)}<br/>
                <a href="/tanah/${record.id}/edit">Edit</a><br/>
                <a href="/print/${record.id}" target="_blank">Print</a>
            </div>
        `;
    }

    if (sismiop) {
        return `
            <div>
                <strong>${sismiop.nama || '-'}</strong><br/>
                NOP: ${formatNopDisplay(sismiop.nop_raw || sismiop.nop)}<br/>
                Alamat: ${sismiop.alamat || '-'}<br/>
                Luas: ${formatNumber(sismiop.luas) || '-'}<br/>
                <a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}&source=sismiop">Tambah dari SISMIOP</a><br/>
                <a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}">Tambah Manual</a>
            </div>
        `;
    }

    return `
        <div>
            NOP: ${formatNopDisplay(nop)}<br/>
            Data administrasi belum tersedia.<br/>
            <a href="/tanah/create?nop=${encodeURIComponent(nop)}">Tambah Manual</a>
        </div>
    `;
};

const selectBidang = async (nopValue, layer = null, preferredTanahId = null) => {
    const nop = normalizeNop(nopValue);
    if (!nop) {
        toast.error('NOP tidak tersedia.');
        return;
    }

    selectedNop.value = nop;
    selectedNopRaw.value = nopValue || null;
    selectedTanahId.value = preferredTanahId || null;
    selectedAdmin.value = null;
    selectedSismiop.value = null;
    selectedStatus.value = 'Memuat data administrasi...';

    const targetLayer = layer || findLayerByNop(nopValue) || findLayerByNop(nop);
    if (targetLayer) {
        focusLayer(targetLayer);
    } else {
        selectedProperties.value = null;
    }

    const records = await fetchTanahRecordsByNop(nop);
    let sismiop = null;

    if (records.length === 0) {
        sismiop = await fetchSismiopData(nop);
        selectedStatus.value = sismiop
            ? 'Data Tanah belum tersedia, data SISMIOP ditemukan'
            : 'Data Tanah belum tersedia';
    } else if (records.length === 1) {
        selectedStatus.value = 'Data Tanah tersedia';
        await fetchTanahDetail(preferredTanahId || records[0].id);
    } else {
        selectedStatus.value = `Ada ${records.length} data administrasi pada bidang ini`;
        const target = preferredTanahId ? records.find((record) => Number(record.id) === Number(preferredTanahId)) : null;
        if (target) {
            await fetchTanahDetail(target.id);
        }
    }

    if (targetLayer) {
        targetLayer.bindPopup(buildPopupHtml(nop, records, sismiop)).openPopup();
    }
};

const viewOnMap = async (item) => {
    if (!item?.id) {
        toast.error('Data tanah tidak tersedia.');
        return;
    }

    selectedTanahId.value = item.id;

    if (item?.nop) {
        await selectBidang(item.nop, null, item.id);
        return;
    }

    await fetchTanahDetail(item.id);
};

const selectTanahRecord = async (record) => {
    selectedTanahId.value = record.id;
    await fetchTanahDetail(record.id);
};

const openHistoryModal = (item) => {
    if (!item?.id) {
        toast.error('Data tanah tidak tersedia.');
        return;
    }

    historyTarget.value = item;
    showHistoryModal.value = true;
};

const closeHistoryModal = () => {
    showHistoryModal.value = false;
    historyTarget.value = null;
};

const refreshSelectedFlow = async (tanahId = selectedTanahId.value, nop = selectedNop.value) => {
    if (nop) {
        await fetchTanahRecordsByNop(nop);
    }

    if (tanahId) {
        await fetchTanahDetail(tanahId);
    }

    router.reload({
        only: ['results'],
        preserveState: true,
        preserveScroll: true,
    });
};

const onHistorySaved = async (payload) => {
    const updatedTanah = payload?.tanah || null;
    const targetId = updatedTanah?.id || historyTarget.value?.id || selectedTanahId.value;
    const targetNop = updatedTanah?.nop || selectedNop.value;

    if (updatedTanah) {
        selectedAdmin.value = updatedTanah;
        selectedTanahId.value = updatedTanah.id;
    }

    await refreshSelectedFlow(targetId, targetNop);
};

watch(() => props.results?.data, async () => {
    if (!selectedTanahId.value) {
        return;
    }

    await fetchTanahDetail(selectedTanahId.value);

    if (selectedNop.value) {
        await fetchTanahRecordsByNop(selectedNop.value);
    }
}, { deep: true });

const formatNopDisplay = (value) => {
    const normalized = normalizeNop(value);
    if (!normalized) {
        return '-';
    }

    if (normalized.length !== 18) {
        return value || normalized;
    }

    return `${normalized.slice(0, 2)}.${normalized.slice(2, 4)}.${normalized.slice(4, 7)}.${normalized.slice(7, 10)}.${normalized.slice(10, 13)}-${normalized.slice(13, 17)}.${normalized.slice(17, 18)}`;
};

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

const formatNumber = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const number = Number(value);
    if (Number.isNaN(number)) {
        return value;
    }

    return Number(number.toFixed(4)).toString();
};

const formatLuasHaDa = (ha, da) => {
    const haLabel = ha !== null && ha !== undefined && ha !== '' ? `${formatNumber(ha)} HA` : null;
    const daLabel = da !== null && da !== undefined && da !== '' ? `${formatNumber(da)} DA` : null;
    return [haLabel, daLabel].filter(Boolean).join(' / ') || '-';
};

const formatLuasAdmin = (admin) => {
    if (!admin) {
        return '-';
    }

    return formatLuasHaDa(admin.luas_sisa_ha ?? admin.luas_ha, admin.luas_sisa_da ?? admin.luas_da);
};

const formatChildLuas = (child) => {
    return formatLuasHaDa(child?.luas_sisa_ha ?? child?.luas_ha, child?.luas_sisa_da ?? child?.luas_da);
};

const formatLuasMap = () => {
    const props = selectedProperties.value || {};
    const value = props.D_LUAS ?? props.d_luas ?? props.luas ?? null;
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return `${formatNumber(value)} m2`;
};
</script>

<template>
    <AppLayout title="Pencarian Data Tanah">
        <Head title="Pencarian" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Pencarian Data Tanah</h1>
            <p class="text-sm text-gray-500 mt-1">Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <SearchBar v-model="search" placeholder="Cari berdasarkan Nama, NOP, Nomor Persil, atau Blok..." />
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-6 space-y-4">
                <Table :headers="headers" :rows="results?.data ?? []" key-field="id">
                    <template #cell-luas_m2="{ row }">
                        {{ row.luas_m2 === null || row.luas_m2 === undefined ? '-' : formatNumber(row.luas_m2) }}
                    </template>
                    <template #cell-actions="{ row }">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="row.nop"
                                @click.stop="viewOnMap(row)"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded transition-colors flex items-center gap-1"
                            >
                                <MapPinned :size="14" />
                                Lihat Peta
                            </button>
                            <span
                                v-else
                                class="px-3 py-1.5 text-xs font-medium text-gray-500 bg-gray-100 rounded"
                            >
                                NOP belum tersedia
                            </span>
                            <a
                                :href="`/tanah/${row.id}/edit`"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors"
                            >
                                Edit
                            </a>
                        </div>
                    </template>
                </Table>

                <Pagination :links="results.links ?? []">
                    <template #info>
                        <span v-if="results?.from && results?.to && results?.total">
                            Showing {{ results.from }}-{{ results.to }} of {{ results.total }}
                        </span>
                        <span v-else>
                            Tidak ada data
                        </span>
                    </template>
                </Pagination>
            </div>

            <div class="xl:col-span-6 space-y-4">
                <div class="bg-white rounded-xl shadow-sm p-3 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm text-gray-600">Basemap</span>
                        <select
                            v-model="basemapId"
                            @change="setBasemap(basemapId)"
                            class="text-sm border border-gray-300 rounded px-2 py-1"
                        >
                            <option v-for="item in basemaps" :key="item.id" :value="item.id">
                                {{ item.label }}
                            </option>
                        </select>
                    </div>
                    <div id="pencarian-map" class="h-[420px] rounded-lg border border-gray-200 bg-gray-100"></div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-4">
                    <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <FileSearch :size="18" />
                        Informasi Bidang Terpilih
                    </h3>

                    <div v-if="loadingBidang" class="text-sm text-gray-500">
                        Memuat polygon...
                    </div>

                    <div v-else-if="selectedNop" class="space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">NOP</span>
                            <span class="font-medium text-gray-900">{{ formatNopDisplay(selectedNopRaw || selectedNop) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status Data</span>
                            <span class="font-medium" :class="selectedAdmin ? 'text-blue-700' : (selectedSismiop ? 'text-lime-700' : 'text-amber-700')">
                                {{ selectedStatus }}
                            </span>
                        </div>

                        <div v-if="tanahRecords.length > 1" class="rounded-lg border border-gray-200 p-3 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold text-gray-900">Data Administrasi pada Bidang Ini</h4>
                                <span class="text-xs text-gray-500">{{ tanahRecords.length }} data</span>
                            </div>
                            <div class="max-h-[280px] space-y-2 overflow-y-auto pr-1">
                                <div
                                    v-for="record in tanahRecords"
                                    :key="record.id"
                                    class="rounded-md border p-3"
                                    :class="Number(selectedTanahId) === Number(record.id) ? 'border-blue-300 bg-blue-50' : 'border-gray-100 bg-gray-50'"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ record.nama_wajib_ipeda || '-' }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ record.status_label || '-' }}
                                                <span v-if="record.parent_name"> dari {{ record.parent_name }}</span>
                                                · Persil: {{ record.nomor_persil || '-' }}
                                            </div>
                                            <div class="text-xs text-gray-700 mt-1">Luas aktif: {{ formatLuasHaDa(record.luas_sisa_ha ?? record.luas_ha, record.luas_sisa_da ?? record.luas_da) }}</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" @click="selectTanahRecord(record)" class="px-2 py-1 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Pilih</button>
                                            <a :href="`/tanah/${record.id}/edit`" class="px-2 py-1 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded">Edit</a>
                                            <a :href="`/print/${record.id}`" target="_blank" class="px-2 py-1 text-xs text-white bg-green-600 hover:bg-green-700 rounded">Print</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="loadingRecords || loadingDetail" class="rounded-lg border border-gray-200 p-3 text-gray-500">
                            Memuat data tanah...
                        </div>

                        <div v-if="selectedAdmin" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Detail Data Administrasi Terpilih</h4>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Status</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.status_label || '-' }}</span></div>
                            <div v-if="selectedAdmin?.parent" class="flex justify-between gap-4"><span class="text-gray-500">Data awal</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin.parent.nama_wajib_ipeda || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">NOP</span><span class="font-medium text-gray-900 text-right">{{ formatNopDisplay(selectedAdmin?.nop_raw || selectedAdmin?.nop || selectedNopRaw || selectedNop) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Blok</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.blok || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Nama Wajib IPEDA</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.nama_wajib_ipeda || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Tempat Tinggal</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.tempat_tinggal || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Jenis Tanah</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.jenis_tanah || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Persil</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.nomor_persil || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">IPEDA R</span><span class="font-medium text-gray-900 text-right">{{ formatNumber(selectedAdmin?.ipeda_r) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">IPEDA S</span><span class="font-medium text-gray-900 text-right">{{ formatNumber(selectedAdmin?.ipeda_s) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Luas Awal</span><span class="font-medium text-gray-900 text-right">{{ formatLuasHaDa(selectedAdmin?.luas_awal_ha ?? selectedAdmin?.luas_ha, selectedAdmin?.luas_awal_da ?? selectedAdmin?.luas_da) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Luas Aktif Saat Ini</span><span class="font-medium text-gray-900 text-right">{{ formatLuasHaDa(selectedAdmin?.luas_sisa_ha ?? selectedAdmin?.luas_ha, selectedAdmin?.luas_sisa_da ?? selectedAdmin?.luas_da) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Status Perubahan</span><span class="font-medium text-gray-900 text-right">{{ (selectedAdmin?.histories?.length || 0) > 0 ? `${selectedAdmin.histories.length} riwayat` : '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Sebab Perubahan</span><span class="font-medium text-gray-900 text-right">{{ selectedAdmin?.sebab_perubahan || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Tanggal Perubahan</span><span class="font-medium text-gray-900 text-right">{{ formatDate(selectedAdmin?.tgl_perubahan) }}</span></div>
                        </div>

                        <div v-if="selectedAdmin?.children?.length" class="rounded-lg border border-gray-200 p-3 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900">Data Hasil Pembagian Tanah</h4>
                            <div class="max-h-[240px] space-y-2 overflow-y-auto pr-1">
                                <div
                                    v-for="child in selectedAdmin.children"
                                    :key="child.id"
                                    class="rounded-md border border-gray-100 bg-gray-50 p-3"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ child.nama_wajib_ipeda || '-' }}</div>
                                            <div class="text-xs text-gray-500">Persil: {{ child.nomor_persil || '-' }} · {{ formatDate(child.tgl_perubahan) }}</div>
                                            <div class="text-xs text-gray-700 mt-1">Luas: {{ formatChildLuas(child) }}</div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" @click="selectTanahRecord(child)" class="px-2 py-1 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Pilih</button>
                                            <a :href="`/tanah/${child.id}/edit`" class="px-2 py-1 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded">Edit</a>
                                            <a :href="`/print/${child.id}`" target="_blank" class="px-2 py-1 text-xs text-white bg-green-600 hover:bg-green-700 rounded">Print</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <TanahHistoryList
                            v-if="selectedAdmin"
                            :histories="selectedAdmin?.histories || []"
                        />

                        <div v-if="!selectedAdmin && selectedSismiop" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Data SISMIOP</h4>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Nama WP</span><span class="font-medium text-gray-900 text-right">{{ selectedSismiop?.nama || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Alamat</span><span class="font-medium text-gray-900 text-right">{{ selectedSismiop?.alamat || '-' }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Luas / NJOP</span><span class="font-medium text-gray-900 text-right">{{ formatNumber(selectedSismiop?.luas || selectedSismiop?.properties?.bumi) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-gray-500">Status</span><span class="font-medium text-lime-700 text-right">Belum masuk Buku C</span></div>
                        </div>

                        <div v-if="!selectedAdmin && !selectedSismiop && tanahRecords.length === 0" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Detail Data Administrasi Terpilih</h4>
                            <div class="text-gray-500">Belum ada data tanah pada bidang ini.</div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a :href="`/peta/map?nop=${encodeURIComponent(selectedNop)}`" class="px-3 py-1.5 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Lihat Peta Penuh</a>
                            <template v-if="selectedAdmin">
                                <a :href="`/tanah/${selectedAdmin.id}/edit`" class="px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded">Edit</a>
                                <a :href="`/print/${selectedAdmin.id}`" target="_blank" class="px-3 py-1.5 text-xs text-white bg-green-600 hover:bg-green-700 rounded">Print</a>
                                <button type="button" @click="openHistoryModal(selectedAdmin)" class="px-3 py-1.5 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Catat Perubahan</button>
                            </template>
                            <template v-else-if="selectedSismiop">
                                <a :href="`/tanah/create?nop=${encodeURIComponent(selectedNop)}&source=sismiop`" class="px-3 py-1.5 text-xs text-white bg-lime-600 hover:bg-lime-700 rounded">Tambah dari SISMIOP</a>
                                <a :href="`/tanah/create?nop=${encodeURIComponent(selectedNop)}`" class="px-3 py-1.5 text-xs text-white bg-amber-600 hover:bg-amber-700 rounded">Tambah Manual</a>
                            </template>
                            <template v-else>
                                <a :href="`/tanah/create?nop=${encodeURIComponent(selectedNop)}`" class="px-3 py-1.5 text-xs text-white bg-amber-600 hover:bg-amber-700 rounded">
                                    Tambah Manual
                                </a>
                            </template>
                        </div>
                    </div>

                    <div v-else class="text-sm text-gray-500">
                        Klik tombol "Lihat Peta" pada tabel atau klik polygon di peta.
                    </div>
                </div>
            </div>
        </div>

        <TanahHistoryModal
            :show="showHistoryModal"
            :tanah="historyTarget"
            @close="closeHistoryModal"
            @saved="onHistorySaved"
        />
    </AppLayout>
</template>
