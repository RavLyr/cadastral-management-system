<template>
  <div class="space-y-3">
    <div class="flex flex-col md:flex-row gap-2">
      <input v-model="search" placeholder="Cari NOP" @keyup.enter="doSearch" class="w-full border rounded px-3 py-2" />
      <button @click="doSearch" class="px-4 py-2 rounded bg-amber-600 text-white">Cari</button>
      <select v-model="basemapId" @change="setBasemap(basemapId)" class="w-full md:w-56 border rounded px-3 py-2">
        <option v-for="item in basemaps" :key="item.id" :value="item.id">
          {{ item.label }}
        </option>
      </select>
    </div>
    <div id="map" style="height:600px;border:1px solid #ccc"></div>
  </div>
</template>

<script>
export default {
  props: {
    initialNop: { type: String, default: null }
  },
  data() {
    return {
      search: this.initialNop || '',
      map: null,
      allLayer: null,
      selectedLayer: null,
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
  mounted() {
    this.loadLeaflet().then(async () => {
      this.initMap();
      await this.loadNopStatuses();
      await this.loadAllPolygons();
      if (this.search) {
        this.doSearch();
      }
    }).catch((err) => {
      console.error('Leaflet load error', err);
    });
  },
  methods: {
    loadLeaflet() {
      return new Promise((resolve, reject) => {
        if (window.L) {
          resolve();
          return;
        }
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Leaflet failed to load'));
        document.body.appendChild(script);
      });
    },
    initMap() {
      this.map = L.map('map').setView([-7.1002, 110.7002], 15);
      this.setBasemap(this.basemapId);
    },
    setBasemap(id) {
      const selected = this.basemaps.find((item) => item.id === id) || this.basemaps[0];
      this.basemapId = selected.id;

      if (this.baseLayer) {
        this.map.removeLayer(this.baseLayer);
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
        console.error(e);
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
      const ha = admin.luas_ha ?? null;
      const da = admin.luas_da ?? null;

      if (ha !== null || da !== null) {
        const haLabel = ha !== null ? `${ha} ha` : null;
        const daLabel = da !== null ? `${da} da` : null;
        return [haLabel, daLabel].filter(Boolean).join(' / ');
      }

      return '-';
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
    buildPopupHtml(nop, admin, sismiop) {
      if (admin) {
        const luasLabel = this.formatLuasAdmin(admin);
        return `<div><strong>${admin.nama_wajib_ipeda || '-'}</strong><br/>NOP: ${this.formatNopDisplay(admin.nop_raw || admin.nop)}<br/>Blok: ${admin.blok || '-'}<br/>Persil: ${admin.nomor_persil || '-'}<br/>Luas: ${luasLabel}<br/><a href="/tanah/${admin.id}/edit">Edit</a><br/><a href="/print/${admin.id}" target="_blank">Print</a></div>`;
      }

      if (sismiop) {
        return `<div><strong>${sismiop.nama || '-'}</strong><br/>NOP: ${this.formatNopDisplay(sismiop.nop_raw || sismiop.nop)}<br/>Alamat: ${sismiop.alamat || '-'}<br/>Luas: ${sismiop.luas || '-'}<br/><a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}&source=sismiop">Tambah dari SISMIOP</a><br/><a href="/tanah/create?nop=${encodeURIComponent(sismiop.nop)}">Tambah Manual</a></div>`;
      }

      return `<div>NOP: ${this.formatNopDisplay(nop)}<br/>Data administrasi belum tersedia.<br/><a href="/tanah/create?nop=${encodeURIComponent(nop)}">Tambah Manual</a></div>`;
    },
    async fetchAdminData(nop) {
      try {
        const res = await fetch(`/api/tanah/by-nop/${encodeURIComponent(nop)}`);
        if (!res.ok) {
          return null;
        }
        return await res.json();
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
        alert('Polygon tidak ditemukan');
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
      const admin = await this.fetchAdminData(nop);
      const sismiop = admin ? null : await this.fetchSismiopData(nop);
      layer.bindPopup(this.buildPopupHtml(nop, admin, sismiop)).openPopup();
    }
  }
}
</script>

<style scoped>
#map { width: 100%; }
</style>
