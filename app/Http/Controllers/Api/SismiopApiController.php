<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SismiopData;
use App\Support\NopNormalizer;

class SismiopApiController extends Controller
{
    public function byNop(string $nop)
    {
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
    }
}
