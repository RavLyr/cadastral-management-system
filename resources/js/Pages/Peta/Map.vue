<template>
  <div class="space-y-3 p-4">
    <div class="flex flex-col md:flex-row gap-2">
      <button @click="goBack" class="px-3 py-2 rounded bg-gray-100 text-gray-700 hover:bg-gray-200">
        Kembali
      </button>
      <input v-model="search" placeholder="Cari NOP" @keyup.enter="doSearch" class="w-full border rounded px-3 py-2" />
      <button @click="doSearch" class="px-4 py-2 rounded bg-amber-600 text-white">Cari</button>
      <select v-model="basemapId" @change="setBasemap(basemapId)" class="w-full md:w-56 border rounded px-3 py-2">
        <option v-for="item in basemaps" :key="item.id" :value="item.id">
          {{ item.label }}
        </option>
      </select>
    </div>
    <div id="map" class="h-[600px] border border-gray-300 bg-gray-100"></div>
    <div v-if="tanahRecords.length > 1" class="bg-white border border-gray-200 rounded-lg p-4 space-y-3">
      <div class="flex items-center justify-between gap-3">
        <h3 class="text-base font-semibold text-gray-900">Data Administrasi pada Bidang Ini</h3>
        <span class="text-xs text-gray-500">{{ tanahRecords.length }} data</span>
      </div>
      <div class="max-h-[280px] space-y-2 overflow-y-auto pr-1">
        <div v-for="record in tanahRecords" :key="record.id" class="rounded-md border p-3 text-sm" :class="Number(selectedTanahId) === Number(record.id) ? 'border-blue-300 bg-blue-50' : 'border-gray-100 bg-gray-50'">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <div class="font-medium text-gray-900">{{ record.nama_wajib_ipeda || '-' }}</div>
              <div class="text-xs text-gray-500">{{ record.status_label || '-' }} · Persil: {{ record.nomor_persil || '-' }}</div>
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
    <div v-if="selectedAdmin" class="bg-white border border-gray-200 rounded-lg p-4 space-y-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-gray-900">Detail Data Administrasi Terpilih</h3>
          <p class="text-sm text-gray-500">{{ selectedAdmin.nama_wajib_ipeda || '-' }} - {{ formatNopDisplay(selectedAdmin.nop_raw || selectedAdmin.nop) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a :href="`/tanah/${selectedAdmin.id}/edit`" class="px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded">Edit</a>
          <a :href="`/print/${selectedAdmin.id}`" target="_blank" class="px-3 py-1.5 text-xs text-white bg-green-600 hover:bg-green-700 rounded">Print</a>
          <button type="button" @click="openHistoryModal(selectedAdmin)" class="px-3 py-1.5 text-xs text-white bg-slate-700 hover:bg-slate-800 rounded">Catat Perubahan</button>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div><span class="text-gray-500">Blok:</span> <span class="font-medium text-gray-900">{{ selectedAdmin.blok || '-' }}</span></div>
        <div><span class="text-gray-500">Persil:</span> <span class="font-medium text-gray-900">{{ selectedAdmin.nomor_persil || '-' }}</span></div>
        <div><span class="text-gray-500">Luas:</span> <span class="font-medium text-gray-900">{{ formatLuasAdmin(selectedAdmin) }}</span></div>
        <div><span class="text-gray-500">Luas awal:</span> <span class="font-medium text-gray-900">{{ formatLuasHaDa(selectedAdmin.luas_awal_ha ?? selectedAdmin.luas_ha, selectedAdmin.luas_awal_da ?? selectedAdmin.luas_da) }}</span></div>
        <div><span class="text-gray-500">Luas aktif saat ini:</span> <span class="font-medium text-gray-900">{{ formatLuasHaDa(selectedAdmin.luas_sisa_ha ?? selectedAdmin.luas_ha, selectedAdmin.luas_sisa_da ?? selectedAdmin.luas_da) }}</span></div>
        <div><span class="text-gray-500">Status perubahan:</span> <span class="font-medium text-gray-900">{{ (selectedAdmin.histories?.length || 0) > 0 ? `${selectedAdmin.histories.length} riwayat` : '-' }}</span></div>
      </div>
      <div v-if="selectedAdmin.children?.length" class="rounded-lg border border-gray-200 p-3 space-y-3">
        <h4 class="text-sm font-semibold text-gray-900">Data Hasil Pembagian Tanah</h4>
        <div class="max-h-[240px] space-y-2 overflow-y-auto pr-1">
          <div v-for="child in selectedAdmin.children" :key="child.id" class="rounded-md border border-gray-100 bg-gray-50 p-3 text-sm">
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
      <TanahHistoryList :histories="selectedAdmin.histories || []" />
    </div>
    <TanahHistoryModal
      :show="showHistoryModal"
      :tanah="historyTarget"
      @close="closeHistoryModal"
      @saved="onHistorySaved"
    />
  </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { toast } from 'vue-sonner';
import TanahHistoryList from '@/Components/TanahHistoryList.vue';
import TanahHistoryModal from '@/Components/TanahHistoryModal.vue';

export default {
  components: {
    TanahHistoryList,
    TanahHistoryModal,
  },
  props: {
    initialNop: { type: String, default: null }
  },
  data() {
    return {
      search: this.initialNop || '',
      map: null,
      allLayer: null,
      selectedLayer: null,
      selectedAdmin: null,
      selectedTanahId: null,
      selectedNop: null,
      tanahRecords: [],
      showHistoryModal: false,
      historyTarget: null,
      nopStatusMap: {},
      basemapId: 'carto',
      baseLayer: null,
      basemaps: [
        {
          id: 'osm',
          label: 'OpenStreetMap',
          url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
          attribution: '&copy; OpenStreetMap contributors'
        },
        {
          id: 'carto',
          label: 'Carto Light',
          url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
          attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
        },
        {
          id: 'esri',
          label: 'Esri Satellite',
          url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
          attribution: 'Tiles &copy; Esri'
        },
        {
          id: 'none',
          label: 'No Basemap (Offline)',
          url: null,
          attribution: ''
        }
      ],
      tanahStyle: {
        color: '#2563eb',
        weight: 1,
        fillColor: '#3b82f6',
        fillOpacity: 0.35,
      },
      sismiopStyle: {
        color: '#65a30d',
        weight: 1,
        fillColor: '#a3e635',
        fillOpacity: 0.3,
      },
      emptyStyle: {
        color: '#ea580c',
        weight: 1,
        fillColor: '#f97316',
        fillOpacity: 0.20,
      },
      highlightStyle: {
        color: '#dc2626',
        weight: 3,
        fillColor: '#ef4444',
        fillOpacity: 0.45,
      }
    };
  },
  async mounted() {
    this.initMap();
    await this.loadNopStatuses();
    await this.loadAllPolygons();

    if (this.search) {
      await this.doSearch();
    }
  },
  methods: {
    initMap() {
      this.map = L.map('map').setView([-7.1002, 110.7002], 15);
      this.setBasemap(this.basemapId);
    },
    setBasemap(id) {
      const selected = this.basemaps.find((item) => item.id === id) || this.basemaps[0];
      this.basemapId = selected.id;

      if (this.baseLayer) {
        this.map.removeLayer(this.baseLayer);
        this.baseLayer = null;
      }

      if (!selected.url) {
        return;
      }

      this.baseLayer = L.tileLayer(selected.url, {
        attribution: selected.attribution,
        maxZoom: 20
      });

      this.baseLayer.addTo(this.map);
    },
    normalizeNop(value) {
      if (!value) {
        return null;
      }

      const digits = String(value).trim().replace(/\D+/g, '');
      return digits || null;
    },
    async loadNopStatuses() {
      try {
        const res = await fetch('/api/gis/nop-status');
        if (!res.ok) {
          return;
        }
        const data = await res.json();
        this.nopStatusMap = data.status || {};
      } catch (e) {
        this.nopStatusMap = {};
      }
    },
    async loadAllPolygons() {
      try {
        const res = await fetch('/api/gis/bidang');
        if (!res.ok) {
          return;
        }

        const data = await res.json();
        if (this.allLayer) {
          this.map.removeLayer(this.allLayer);
        }

        this.allLayer = L.geoJSON(data, {
          style: (feature) => this.getBaseStyle(feature?.properties?.nop),
          onEachFeature: (feature, layer) => {
            const nop = feature?.properties?.nop;
            layer.on('click', async () => {
              await this.onPolygonClick(nop, layer);
            });
          }
        }).addTo(this.map);

        const bounds = this.allLayer.getBounds();
        if (bounds.isValid()) {
          this.map.fitBounds(bounds.pad(0.08));
        }
      } catch (e) {
        toast.error('Gagal memuat polygon bidang tanah.');
      }
    },
    findLayerByNop(nop) {
      let found = null;
      if (!this.allLayer) {
        return null;
      }
      this.allLayer.eachLayer((layer) => {
        const layerNop = this.normalizeNop(layer?.feature?.properties?.nop);
        const needle = this.normalizeNop(nop);
        if (layerNop && needle && layerNop === needle) {
          found = layer;
        }
      });
      return found;
    },
    highlightLayer(layer) {
      if (!layer || !layer.setStyle) {
        return;
      }
      if (this.selectedLayer && this.selectedLayer !== layer && this.selectedLayer.setStyle) {
        const nop = this.selectedLayer?.feature?.properties?.nop;
        this.selectedLayer.setStyle(this.getBaseStyle(nop));
      }
      this.selectedLayer = layer;
      layer.setStyle(this.highlightStyle);
      if (layer.bringToFront) {
        layer.bringToFront();
      }
    },
    getBaseStyle(nop) {
      const normalized = this.normalizeNop(nop);
      const status = normalized ? this.nopStatusMap[normalized] : 'empty';

      if (status === 'tanah') {
        return { ...this.tanahStyle };
      }

      if (status === 'sismiop') {
        return { ...this.sismiopStyle };
      }

      return { ...this.emptyStyle };
    },
    formatLuasAdmin(admin) {
      if (!admin) {
        return '-';
      }
      return this.formatLuasHaDa(admin.luas_sisa_ha ?? admin.luas_ha, admin.luas_sisa_da ?? admin.luas_da);
    },
    formatNumber(value) {
      if (value === null || value === undefined || value === '') {
        return '-';
      }

      const number = Number(value);
      if (Number.isNaN(number)) {
        return value;
      }

      return Number(number.toFixed(4)).toString();
    },
    formatLuasHaDa(ha, da) {
      const haLabel = ha !== null && ha !== undefined && ha !== '' ? `${this.formatNumber(ha)} HA` : null;
      const daLabel = da !== null && da !== undefined && da !== '' ? `${this.formatNumber(da)} DA` : null;
      return [haLabel, daLabel].filter(Boolean).join(' / ') || '-';
    },
    formatChildLuas(child) {
      return this.formatLuasHaDa(child?.luas_sisa_ha ?? child?.luas_ha, child?.luas_sisa_da ?? child?.luas_da);
    },
    formatDate(value) {
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
    },
    formatNopDisplay(value) {
      const normalized = this.normalizeNop(value);
      if (!normalized) {
        return '-';
      }

      if (normalized.length !== 18) {
        return value || normalized;
      }

      return `${normalized.slice(0, 2)}.${normalized.slice(2, 4)}.${normalized.slice(4, 7)}.${normalized.slice(7, 10)}.${normalized.slice(10, 13)}-${normalized.slice(13, 17)}.${normalized.slice(17, 18)}`;
    },
    buildPopupHtml(nop, records, sismiop) {
      if (records.length > 1) {
        return `<div><strong>Tanah dimiliki beberapa pihak</strong><br/>NOP: ${this.formatNopDisplay(nop)}<br/>Ada ${records.length} data administrasi pada bidang ini.<br/>Pilih data tanah dari panel.</div>`;
      }

      if (records.length === 1) {
        const admin = records[0];
        const luasLabel = this.formatLuasHaDa(admin.luas_sisa_ha ?? admin.luas_ha, admin.luas_sisa_da ?? admin.luas_da);
        return `<div><strong>${admin.nama_wajib_ipeda || '-'}</strong><br/>NOP: ${this.formatNopDisplay(admin.nop_raw || admin.nop)}<br/>Status: ${admin.status_label || '-'}<br/>Persil: ${admin.nomor_persil || '-'}<br/>Luas aktif: ${luasLabel}<br/><a href="/tanah/${admin.id}/edit">Edit</a><br/><a href="/print/${admin.id}" target="_blank">Print</a></div>`;
      }

      if (sismiop) {
        return `<div><strong>${sismiop.nama || '-'}</strong><br/>NOP: ${this.formatNopDisplay(sismiop.nop_raw || sismiop.nop)}<br/>Alamat: ${sismiop.alamat || '-'}<br/>Luas: ${sismiop.luas || '-'}<br/><a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}&source=sismiop">Tambah dari SISMIOP</a><br/><a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}">Tambah Manual</a></div>`;
      }

      return `<div>NOP: ${this.formatNopDisplay(nop)}<br/>Data administrasi belum tersedia.<br/><a href="/tanah/create?nop=${encodeURIComponent(nop)}">Tambah Manual</a></div>`;
    },
    async fetchTanahRecordsByNop(nop) {
      try {
        const res = await fetch(`/api/tanah/by-nop/${encodeURIComponent(nop)}/records`);
        if (!res.ok) {
          this.tanahRecords = [];
          return [];
        }
        const data = await res.json();
        this.tanahRecords = data.records || [];
        return this.tanahRecords;
      } catch (e) {
        this.tanahRecords = [];
        return [];
      }
    },
    async fetchTanahDetail(id) {
      try {
        const res = await fetch(`/api/tanah/${encodeURIComponent(id)}/detail`);
        if (!res.ok) {
          return null;
        }

        const detail = await res.json();
        this.selectedTanahId = detail.id;
        this.selectedAdmin = detail;
        return detail;
      } catch (e) {
        return null;
      }
    },
    async fetchSismiopData(nop) {
      try {
        const res = await fetch(`/api/sismiop/by-nop/${encodeURIComponent(nop)}`);
        if (!res.ok) {
          return null;
        }
        return await res.json();
      } catch (e) {
        return null;
      }
    },
    async doSearch() {
      const nop = this.normalizeNop(this.search && this.search.trim());
      if (!nop) return;

      const layer = this.findLayerByNop(nop);
      if (!layer) {
        toast.error('Polygon tidak ditemukan pada peta.');
        return;
      }

      await this.onPolygonClick(nop, layer);
      const bounds = layer.getBounds();
      if (bounds.isValid && !bounds.isEmpty()) {
        this.map.fitBounds(bounds.pad(0.2));
      }
    },
    async onPolygonClick(nopValue, layer) {
      const nop = this.normalizeNop(nopValue);
      if (!nop) {
        return;
      }

      this.highlightLayer(layer);
      this.selectedNop = nop;
      this.selectedAdmin = null;
      this.selectedTanahId = null;

      const records = await this.fetchTanahRecordsByNop(nop);
      const sismiop = records.length ? null : await this.fetchSismiopData(nop);

      if (records.length === 1) {
        await this.fetchTanahDetail(records[0].id);
      }

      layer.bindPopup(this.buildPopupHtml(nop, records, sismiop)).openPopup();
    }
    ,
    async selectTanahRecord(record) {
      if (!record?.id) {
        return;
      }

      await this.fetchTanahDetail(record.id);
    },
    openHistoryModal(admin) {
      if (!admin?.id) {
        toast.error('Data tanah tidak tersedia.');
        return;
      }

      this.historyTarget = admin;
      this.showHistoryModal = true;
    },
    closeHistoryModal() {
      this.showHistoryModal = false;
      this.historyTarget = null;
    },
    async onHistorySaved(payload) {
      const updatedTanah = payload?.tanah || null;
      const targetId = updatedTanah?.id || this.historyTarget?.id || this.selectedTanahId;

      if (updatedTanah) {
        this.selectedAdmin = updatedTanah;
        this.selectedTanahId = updatedTanah.id;
      }

      if (this.selectedNop) {
        await this.fetchTanahRecordsByNop(this.selectedNop);
      }

      if (targetId) {
        await this.fetchTanahDetail(targetId);
      }
    },
    goBack() {
      if (window.history.length > 1) {
        window.history.back();
        return;
      }

      window.location.href = '/pencarian';
    }
  }
}
</script>

<style scoped>
#map { width: 100%; }
</style>
