<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SismiopData;
use App\Models\Tanah;
use App\Support\NopNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GisApiController extends Controller
{
    public function bidang()
    {
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
    }

    public function showBidang(string $nop)
    {
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
    }

    public function nopStatus()
    {
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
    }
}
