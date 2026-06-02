<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tanah;
use App\Support\NopNormalizer;

class TanahApiController extends Controller
{
    public function byNop(string $nop)
    {
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
    }

    public function nopList()
    {
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
    }
}
