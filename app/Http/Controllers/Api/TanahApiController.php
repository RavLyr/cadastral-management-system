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

            $row = Tanah::with(['blok', 'histories', 'children.blok'])
                ->where('nop', $normalizedNop)
                ->whereNull('parent_id')
                ->first();

            if (! $row) {
                return response()->json(['message' => 'Data administrasi tanah tidak ditemukan.'], 404);
            }

            return response()->json($this->serializeTanahDetail($row));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function recordsByNop(string $nop)
    {
        try {
            $normalizedNop = NopNormalizer::normalize($nop);
            if (! $normalizedNop) {
                return response()->json(['message' => 'NOP tidak valid.'], 422);
            }

            $records = Tanah::with(['blok:id,nama_blok', 'parent:id,nama_wajib_ipeda'])
                ->where('nop', $normalizedNop)
                ->orderByRaw('case when parent_id is null then 0 else 1 end')
                ->orderBy('id')
                ->get();

            return response()->json([
                'nop' => $normalizedNop,
                'total_records' => $records->count(),
                'records' => $records->map(fn (Tanah $tanah) => $this->serializeTanahRecord($tanah)),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function detail(Tanah $tanah)
    {
        try {
            $tanah->load(['blok', 'parent.blok', 'histories', 'children.blok']);

            return response()->json($this->serializeTanahDetail($tanah));
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

    private function serializeTanahDetail(Tanah $tanah): array
    {
        return [
            'id' => $tanah->id,
            'parent_id' => $tanah->parent_id,
            'nop' => $tanah->nop,
            'nop_raw' => $tanah->nop_raw,
            'nama_wajib_ipeda' => $tanah->nama_wajib_ipeda,
            'jenis_tanah' => $tanah->jenis_tanah,
            'blok' => $tanah->blok?->nama_blok,
            'blok_id' => $tanah->blok_id,
            'nomor_persil' => $tanah->nomor_persil,
            'kelas_desa' => $tanah->kelas_desa,
            'luas_ha' => $tanah->luas_ha,
            'luas_da' => $tanah->luas_da,
            'luas_awal_ha' => $tanah->luas_awal_ha,
            'luas_awal_da' => $tanah->luas_awal_da,
            'luas_sisa_ha' => $tanah->luas_sisa_ha,
            'luas_sisa_da' => $tanah->luas_sisa_da,
            'tempat_tinggal' => $tanah->tempat_tinggal,
            'ipeda_r' => $tanah->ipeda_r,
            'ipeda_s' => $tanah->ipeda_s,
            'sebab_perubahan' => $tanah->sebab_perubahan,
            'tgl_perubahan' => $tanah->tgl_perubahan?->format('Y-m-d'),
            'status_label' => $tanah->parent_id ? 'Hasil pembagian' : 'Data awal',
            'parent' => $tanah->parent ? $this->serializeTanahRecord($tanah->parent) : null,
            'histories' => $tanah->histories->map(fn ($history) => [
                'id' => $history->id,
                'jenis_perubahan' => $history->jenis_perubahan,
                'tanggal_perubahan' => $history->tanggal_perubahan?->format('Y-m-d'),
                'luas_awal' => $history->luas_awal,
                'luas_awal_da' => $history->luas_awal_da,
                'luas_berubah' => $history->luas_berubah,
                'luas_berubah_da' => $history->luas_berubah_da,
                'luas_sisa' => $history->luas_sisa,
                'luas_sisa_da' => $history->luas_sisa_da,
                'pemilik_lama' => $history->pemilik_lama,
                'pemilik_baru' => $history->pemilik_baru,
                'keterangan' => $history->keterangan,
            ]),
            'children' => $tanah->children->map(fn ($child) => $this->serializeTanahRecord($child)),
        ];
    }

    private function serializeTanahRecord(Tanah $tanah): array
    {
        return [
            'id' => $tanah->id,
            'parent_id' => $tanah->parent_id,
            'nop' => $tanah->nop,
            'nop_raw' => $tanah->nop_raw,
            'nama_wajib_ipeda' => $tanah->nama_wajib_ipeda,
            'nomor_persil' => $tanah->nomor_persil,
            'blok' => $tanah->blok?->nama_blok,
            'blok_id' => $tanah->blok_id,
            'jenis_tanah' => $tanah->jenis_tanah,
            'luas_ha' => $tanah->luas_ha,
            'luas_da' => $tanah->luas_da,
            'luas_awal_ha' => $tanah->luas_awal_ha,
            'luas_awal_da' => $tanah->luas_awal_da,
            'luas_sisa_ha' => $tanah->luas_sisa_ha,
            'luas_sisa_da' => $tanah->luas_sisa_da,
            'tgl_perubahan' => $tanah->tgl_perubahan?->format('Y-m-d'),
            'status_label' => $tanah->parent_id ? 'Hasil pembagian' : 'Data awal',
            'parent_name' => $tanah->parent?->nama_wajib_ipeda,
        ];
    }
}
