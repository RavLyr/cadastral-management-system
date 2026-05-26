<?php

use App\Models\SismiopData;
use App\Models\Tanah;
use App\Support\NopNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/gis/bidang', function (Request $request) {
    try {
        $conn = DB::connection('pgsql');
        $table = $conn->selectOne("SELECT to_regclass('public.gis_bidang_tanah') as exists");

        if (! $table || ! $table->exists) {
            return response()->json(['error' => 'gis_bidang_tanah table not found.'], 404);
        }

        $hasNopRaw = Schema::hasColumn('gis_bidang_tanah', 'nop_raw');

        $rows = $hasNopRaw
            ? $conn->select('SELECT nop, nop_raw, properties, ST_AsGeoJSON(geom)::json as geometry FROM gis_bidang_tanah ORDER BY id')
            : $conn->select('SELECT nop, properties, ST_AsGeoJSON(geom)::json as geometry FROM gis_bidang_tanah ORDER BY id');

        $features = [];
        foreach ($rows as $row) {
            $properties = json_decode($row->properties ?? '{}', true) ?: [];
            $properties['nop'] = $row->nop;
            if (property_exists($row, 'nop_raw')) {
                $properties['nop_raw'] = $row->nop_raw;
            }

            $features[] = [
                'type' => 'Feature',
                'properties' => $properties,
                'geometry' => json_decode($row->geometry, true),
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/gis/bidang/{nop}', function (string $nop) {
    try {
        $normalizedNop = NopNormalizer::normalize($nop);
        if (! $normalizedNop) {
            return response()->json(['message' => 'NOP tidak valid.'], 422);
        }

        $conn = DB::connection('pgsql');
        $table = $conn->selectOne("SELECT to_regclass('public.gis_bidang_tanah') as exists");

        if (! $table || ! $table->exists) {
            return response()->json(['error' => 'gis_bidang_tanah table not found.'], 404);
        }

        $hasNopRaw = Schema::hasColumn('gis_bidang_tanah', 'nop_raw');

        $row = $hasNopRaw
            ? $conn->selectOne('SELECT nop, nop_raw, properties, ST_AsGeoJSON(geom)::json as geometry FROM gis_bidang_tanah WHERE nop = ? LIMIT 1', [$normalizedNop])
            : $conn->selectOne('SELECT nop, properties, ST_AsGeoJSON(geom)::json as geometry FROM gis_bidang_tanah WHERE nop = ? LIMIT 1', [$normalizedNop]);

        if (! $row) {
            return response()->json(['message' => 'Data bidang tanah tidak ditemukan.'], 404);
        }

        return response()->json([
            'nop' => $row->nop,
            'nop_raw' => $row->nop_raw ?? null,
            'geometry' => json_decode($row->geometry, true),
            'properties' => json_decode($row->properties, true),
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/tanah/by-nop/{nop}', function (string $nop) {
    try {
        $normalizedNop = NopNormalizer::normalize($nop);
        if (! $normalizedNop) {
            return response()->json(['message' => 'NOP tidak valid.'], 422);
        }

        $row = Tanah::with('blok')
            ->where('nop', $normalizedNop)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Data administrasi tanah tidak ditemukan.'], 404);
        }

        return response()->json([
            'id' => $row->id,
            'nop' => $row->nop,
            'nop_raw' => $row->nop_raw,
            'nama_wajib_ipeda' => $row->nama_wajib_ipeda,
            'jenis_tanah' => $row->jenis_tanah,
            'blok' => $row->blok?->nama_blok,
            'nomor_persil' => $row->nomor_persil,
            'luas_ha' => $row->luas_ha,
            'luas_da' => $row->luas_da,
            'tempat_tinggal' => $row->tempat_tinggal,
            'ipeda_r' => $row->ipeda_r,
            'ipeda_s' => $row->ipeda_s,
            'sebab_perubahan' => $row->sebab_perubahan,
            'tgl_perubahan' => $row->tgl_perubahan,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/tanah/nop-list', function () {
    try {
        $nops = Tanah::query()
            ->whereNotNull('nop')
            ->where('nop', '!=', '')
            ->distinct()
            ->pluck('nop')
            ->values();

        return response()->json(['nops' => $nops]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/sismiop/by-nop/{nop}', function (string $nop) {
    try {
        $normalizedNop = NopNormalizer::normalize($nop);
        if (! $normalizedNop) {
            return response()->json(['message' => 'NOP tidak valid.'], 422);
        }

        $row = SismiopData::query()
            ->where('nop', $normalizedNop)
            ->first();

        if (! $row) {
            return response()->json(['message' => 'Data referensi SISMIOP tidak ditemukan.'], 404);
        }

        return response()->json([
            'nop' => $row->nop,
            'nop_raw' => $row->nop_raw ?? null,
            'nama' => $row->subjek_pajak_nama_wajib_pajak,
            'alamat' => trim(implode(' ', array_filter([
                $row->subjek_pajak_jalan_dusun,
                $row->subjek_pajak_desa_kel,
                $row->subjek_pajak_kabupaten_kota,
            ]))),
            'luas' => $row->bumi,
            'properties' => [
                'objek_pajak_jalan_dusun_op' => $row->objek_pajak_jalan_dusun_op,
                'objek_pajak_rt' => $row->objek_pajak_rt,
                'objek_pajak_rw' => $row->objek_pajak_rw,
                'objek_pajak_desa' => $row->objek_pajak_desa,
                'subjek_pajak_nama_wajib_pajak' => $row->subjek_pajak_nama_wajib_pajak,
                'subjek_pajak_jalan_dusun' => $row->subjek_pajak_jalan_dusun,
                'subjek_pajak_rt' => $row->subjek_pajak_rt,
                'subjek_pajak_rw' => $row->subjek_pajak_rw,
                'subjek_pajak_desa_kel' => $row->subjek_pajak_desa_kel,
                'subjek_pajak_kabupaten_kota' => $row->subjek_pajak_kabupaten_kota,
                'bumi' => $row->bumi,
                'bng' => $row->bng,
                'jns_bumi' => $row->jns_bumi,
                'usulan_pembetulan' => $row->usulan_pembetulan,
                'blok' => $row->blok,
                'no_urut' => $row->no_urut,
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/gis/nop-status', function () {
    try {
        $tanahNops = Tanah::query()
            ->whereNotNull('nop')
            ->where('nop', '!=', '')
            ->distinct()
            ->pluck('nop')
            ->map(fn ($nop) => (string) $nop)
            ->values();

        $sismiopNops = SismiopData::query()
            ->whereNotNull('nop')
            ->where('nop', '!=', '')
            ->distinct()
            ->pluck('nop')
            ->map(fn ($nop) => (string) $nop)
            ->values();

        $tanahSet = array_fill_keys($tanahNops->all(), true);
        $sismiopSet = array_fill_keys($sismiopNops->all(), true);

        $gisNops = DB::table('gis_bidang_tanah')
            ->whereNotNull('nop')
            ->pluck('nop')
            ->map(fn ($nop) => (string) $nop);

        $status = [];
        foreach ($gisNops as $nop) {
            if (isset($tanahSet[$nop])) {
                $status[$nop] = 'tanah';
            } elseif (isset($sismiopSet[$nop])) {
                $status[$nop] = 'sismiop';
            } else {
                $status[$nop] = 'empty';
            }
        }

        return response()->json([
            'tanah_nops' => $tanahNops,
            'sismiop_nops' => $sismiopNops,
            'status' => $status,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});