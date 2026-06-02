<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { FileSearch, MapPinned, Printer } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import SearchBar from '@/Components/SearchBar.vue';
import Table from '@/Components/Table.vue';
import Pagination from '@/Components/Pagination.vue';

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
    { label: 'LUAS (M²)', key: 'luas_m2' },
    { label: 'AKSI', key: 'actions' },
];

const search = ref(props.filters.search || '');
const selectedNop = ref(null);
const selectedNopRaw = ref(null);
const selectedAdmin = ref(null);
const selectedSismiop = ref(null);
const selectedProperties = ref(null);
const selectedStatus = ref('');
const mapReady = ref(false);
const loadingBidang = ref(false);
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

const findLayerByNop = (nop) => {
    let found = null;
    const needle = normalizeNop(nop);

    if (!allBidangLayer.value) {
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

const buildPopupHtml = (nop, admin, sismiop) => {
    if (admin) {
        const luasLabel = formatLuasAdmin(admin);
        return `
            <div>
                <strong>${admin.nama_wajib_ipeda || '-'}</strong><br/>
                NOP: ${formatNopDisplay(admin.nop_raw || admin.nop)}<br/>
                Blok: ${admin.blok || '-'}<br/>
                Persil: ${admin.nomor_persil || '-'}<br/>
                Luas: ${luasLabel}<br/>
                <a href="/tanah/${admin.id}/edit">Edit</a><br/>
                <a href="/print/${admin.id}" target="_blank">Print</a>
            </div>
        `;
    }

    if (sismiop) {
        return `
            <div>
                <strong>${sismiop.nama || '-'}</strong><br/>
                NOP: ${formatNopDisplay(sismiop.nop_raw || sismiop.nop)}<br/>
                Alamat: ${sismiop.alamat || '-'}<br/>
                Luas: ${sismiop.luas ?? '-'}<br/>
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

const fetchAdminData = async (nop) => {
    try {
        const res = await fetch(`/api/tanah/by-nop/${encodeURIComponent(nop)}`);

        if (res.status === 404) {
            selectedAdmin.value = null;
            return null;
        }

        if (!res.ok) {
            selectedAdmin.value = null;
            selectedStatus.value = 'Gagal memuat data administrasi';
            return null;
        }

        const admin = await res.json();
        selectedAdmin.value = admin;
        return admin;
    } catch (error) {
        selectedAdmin.value = null;
        return null;
    }
};

const fetchSismiopData = async (nop) => {
    try {
        const res = await fetch(`/api/sismiop/by-nop/${encodeURIComponent(nop)}`);

        if (res.status === 404) {
            selectedSismiop.value = null;
            return null;
        }

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

const selectBidang = async (nopValue, layer = null) => {
    const nop = normalizeNop(nopValue);
    if (!nop) {
        toast.error('NOP tidak tersedia.');
        return;
    }

    selectedNop.value = nop;
    selectedNopRaw.value = nopValue || null;
    selectedAdmin.value = null;
    selectedSismiop.value = null;
    selectedStatus.value = 'Memuat data administrasi...';

    const targetLayer = layer || findLayerByNop(nopValue) || findLayerByNop(nop);
    if (!targetLayer) {
        toast.error('Polygon tidak ditemukan pada peta.');
        return;
    }

    selectedProperties.value = targetLayer?.feature?.properties || null;

    markSelectedLayer(targetLayer);

    const bounds = targetLayer.getBounds();
    if (bounds.isValid()) {
        map.value.fitBounds(bounds.pad(0.2));
    }

    const admin = await fetchAdminData(nop);
    let sismiop = null;

    if (admin) {
        selectedStatus.value = 'Data Buku C tersedia';
    } else {
        sismiop = await fetchSismiopData(nop);

        if (sismiop) {
            selectedStatus.value = 'Data Buku C belum tersedia, data SISMIOP ditemukan';
        } else {
            selectedStatus.value = 'Data administrasi belum tersedia';
        }
    }

    targetLayer.bindPopup(buildPopupHtml(nop, admin, sismiop)).openPopup();
};

const viewOnMap = async (item) => {
    if (!item?.nop) {
        toast.error('NOP belum tersedia pada data ini.');
        return;
    }

    await selectBidang(item.nop);
};

const printPdf = (item) => {
    window.open(`/print/${item.id}`, '_blank');
};

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

const formatLuasAdmin = (admin) => {
    if (!admin) {
        return '-';
    }
    const ha = admin.luas_ha ?? null;
    const da = admin.luas_da ?? null;

    if (ha !== null || da !== null) {
        const haLabel = ha !== null ? `${ha} ha` : null;
        const daLabel = da !== null ? `${da} da` : null;
        return [haLabel, daLabel].filter(Boolean).join(' / ');
    }

    return '-';
};

const formatLuasMap = () => {
    const props = selectedProperties.value || {};
    const value = props.D_LUAS ?? props.d_luas ?? props.luas ?? null;
    if (value === null || value === undefined || value === '') {
        return '-';
    }
    return `${value} m²`;
};

const formatLuasForDisplay = () => {
    if (selectedAdmin.value) {
        const adminLuas = formatLuasAdmin(selectedAdmin.value);
        if (adminLuas !== '-') {
            return adminLuas;
        }
    }

    if (selectedSismiop.value?.luas) {
        return selectedSismiop.value.luas;
    }

    return formatLuasMap();
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
                                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded transition-colors flex items-center gap-1"
                            >
                                Edit
                            </a>
                            <button
                                @click.stop="printPdf(row)"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded transition-colors flex items-center gap-1"
                            >
                                <Printer :size="14" />
                                Print
                            </button>
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

                        <div class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Detail Peta</h4>
                            <div class="flex justify-between"><span class="text-gray-500">Blok</span><span class="font-medium text-gray-900">{{ selectedAdmin?.blok || selectedSismiop?.properties?.blok || selectedProperties?.D_BLOK || selectedProperties?.blok || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Nomor Persil / Objek</span><span class="font-medium text-gray-900">{{ selectedAdmin?.nomor_persil || selectedSismiop?.properties?.no_urut || selectedProperties?.D_NO_URUT || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Luas</span><span class="font-medium text-gray-900">{{ formatLuasForDisplay() }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Skala Peta</span><span class="font-medium text-gray-900">{{ selectedProperties?.skala || selectedProperties?.D_SKA || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">NOP</span><span class="font-medium text-gray-900">{{ formatNopDisplay(selectedNopRaw || selectedNop) }}</span></div>
                        </div>

                        <div v-if="selectedAdmin" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Detail Data Tanah</h4>
                            <div class="flex justify-between"><span class="text-gray-500">Nama Wajib IPEDA</span><span class="font-medium text-gray-900">{{ selectedAdmin?.nama_wajib_ipeda || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Tempat Tinggal</span><span class="font-medium text-gray-900">{{ selectedAdmin?.tempat_tinggal || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">IPEDA R</span><span class="font-medium text-gray-900">{{ selectedAdmin?.ipeda_r ?? '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">IPEDA S</span><span class="font-medium text-gray-900">{{ selectedAdmin?.ipeda_s ?? '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Sebab Perubahan</span><span class="font-medium text-gray-900">{{ selectedAdmin?.sebab_perubahan || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Tanggal Perubahan</span><span class="font-medium text-gray-900">{{ formatDate(selectedAdmin?.tgl_perubahan) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Jenis Tanah</span><span class="font-medium text-gray-900">{{ selectedAdmin?.jenis_tanah || '-' }}</span></div>
                        </div>

                        <div v-else-if="selectedSismiop" class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Data SISMIOP</h4>
                            <div class="flex justify-between"><span class="text-gray-500">Nama WP</span><span class="font-medium text-gray-900">{{ selectedSismiop?.nama || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="font-medium text-gray-900">{{ selectedSismiop?.alamat || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Luas / NJOP</span><span class="font-medium text-gray-900">{{ selectedSismiop?.luas || selectedSismiop?.properties?.bumi || '-' }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium text-lime-700">Belum masuk Buku C</span></div>
                        </div>

                        <div v-else class="rounded-lg border border-gray-200 p-3 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-900">Detail Data Tanah</h4>
                            <div class="text-gray-500">-</div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a :href="`/peta/map?nop=${encodeURIComponent(selectedNop)}`" class="px-3 py-1.5 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Lihat Peta Penuh</a>
                            <template v-if="selectedAdmin">
                                <a :href="`/tanah/${selectedAdmin.id}/edit`" class="px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded">Edit</a>
                                <a :href="`/print/${selectedAdmin.id}`" target="_blank" class="px-3 py-1.5 text-xs text-white bg-green-600 hover:bg-green-700 rounded">Print</a>
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
    </AppLayout>
</template>
