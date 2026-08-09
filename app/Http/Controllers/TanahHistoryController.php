<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

class TanahHistoryController extends Controller
{
    public function store(Request $request, Tanah $tanah)
    {
        $validated = $request->validate([
            'jenis_perubahan' => ['required', 'string', 'max:255'],
            'tanggal_perubahan' => ['nullable', 'date'],
            'luas_awal' => ['nullable', 'numeric', 'min:0'],
            'luas_awal_da' => ['nullable', 'numeric', 'min:0'],
            'luas_berubah' => ['nullable', 'numeric', 'min:0'],
            'luas_berubah_da' => ['nullable', 'numeric', 'min:0'],
            'luas_sisa' => ['nullable', 'numeric', 'min:0'],
            'luas_sisa_da' => ['nullable', 'numeric', 'min:0'],
            'pemilik_lama' => ['nullable', 'string', 'max:255'],
            'pemilik_baru' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'create_child' => ['nullable', 'boolean'],
            'child_nop' => ['nullable', 'string', 'max:64'],
            'child_nama_wajib_ipeda' => ['nullable', 'string', 'max:255'],
            'child_tempat_tinggal' => ['nullable', 'string', 'max:255'],
            'child_jenis_tanah' => ['nullable', 'in:basah,kering'],
            'child_blok_id' => ['nullable', 'exists:map_blok,id'],
            'child_nomor_persil' => ['nullable', 'string', 'max:50'],
            'child_kelas_desa' => ['nullable', 'string', 'max:50'],
            'child_luas_ha' => ['nullable', 'numeric', 'min:0'],
            'child_luas_da' => ['nullable', 'numeric', 'min:0'],
            'child_ipeda_r' => ['nullable', 'numeric', 'min:0'],
            'child_ipeda_s' => ['nullable', 'numeric', 'min:0'],
            'child_sebab_perubahan' => ['nullable', 'string'],
            'child_tgl_perubahan' => ['nullable', 'date'],
        ]);

        $isPartialSale = $this->isPartialSale($validated['jenis_perubahan']);
        $isOwnerChange = $this->isOwnerChange($validated['jenis_perubahan']);
        $isWaris = mb_strtolower(trim($validated['jenis_perubahan'])) === 'waris';
        $isHibah = mb_strtolower(trim($validated['jenis_perubahan'])) === 'hibah';
        $isKoreksi = mb_strtolower(trim($validated['jenis_perubahan'])) === 'koreksi data';

        // Sanitasi data berdasarkan jenis perubahan
        if (!$isPartialSale) {
            $validated['luas_awal'] = null;
            $validated['luas_awal_da'] = null;
            $validated['luas_berubah'] = null;
            $validated['luas_berubah_da'] = null;
            $validated['luas_sisa'] = null;
            $validated['luas_sisa_da'] = null;
        }

        if ($isKoreksi) {
            $validated['pemilik_lama'] = null;
            $validated['pemilik_baru'] = null;
        }

        $childPayload = $this->extractChildPayload($validated);

        unset($validated['create_child']);

        $basisHa = $this->numberOrNull($tanah->luas_sisa_ha ?? $tanah->luas_ha);
        $basisDa = $this->numberOrNull($tanah->luas_sisa_da ?? $tanah->luas_da);
        $changedHa = $this->numberOrNull($validated['luas_berubah'] ?? null);
        $changedDa = $this->numberOrNull($validated['luas_berubah_da'] ?? null);
        $newSisaHa = $basisHa;
        $newSisaDa = $basisDa;

        if ($isPartialSale) {
            [$newSisaHa, $newSisaDa] = $this->validatePartialSale(
                $basisHa,
                $basisDa,
                $changedHa,
                $changedDa,
                $validated
            );

            if (trim((string) ($validated['pemilik_baru'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'pemilik_baru' => 'Pemilik baru wajib diisi untuk perubahan dijual sebagian.',
                ]);
            }

            $validated['luas_awal'] = $basisHa;
            $validated['luas_awal_da'] = $basisDa;
            $validated['luas_sisa'] = $newSisaHa;
            $validated['luas_sisa_da'] = $newSisaDa;

            $this->validateChildPersil($tanah, $childPayload);
        }

        if ($isOwnerChange && trim((string) ($validated['pemilik_baru'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'pemilik_baru' => 'Pemilik baru wajib diisi untuk perubahan ganti pemilik.',
            ]);
        }

        if ($isWaris && trim((string) ($validated['pemilik_baru'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'pemilik_baru' => 'Ahli waris/pemilik baru wajib diisi.',
            ]);
        }

        if ($isHibah) {
            if (trim((string) ($validated['pemilik_lama'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'pemilik_lama' => 'Pemberi wajib diisi.',
                ]);
            }
            if (trim((string) ($validated['pemilik_baru'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'pemilik_baru' => 'Penerima wajib diisi.',
                ]);
            }
        }

        $result = DB::transaction(function () use ($tanah, $validated, $isPartialSale, $isOwnerChange, $childPayload, $newSisaHa, $newSisaDa, $changedHa, $changedDa) {
            $history = $tanah->histories()->create([
                ...$validated,
                'created_by' => Auth::id(),
            ]);

            if ($isPartialSale) {
                $tanah->update([
                    'luas_awal_ha' => $tanah->luas_awal_ha ?? $tanah->luas_ha,
                    'luas_awal_da' => $tanah->luas_awal_da ?? $tanah->luas_da,
                    'luas_sisa_ha' => $newSisaHa,
                    'luas_sisa_da' => $newSisaDa,
                    'luas_ha' => $newSisaHa,
                    'luas_da' => $newSisaDa,
                ]);
            }

            if ($isOwnerChange) {
                $tanah->update([
                    'nama_wajib_ipeda' => $validated['pemilik_baru'],
                ]);
            }

            if ($isPartialSale) {
                $childHa = $this->numberOrNull($childPayload['child_luas_ha'] ?? null) ?? $changedHa;
                $childDa = $this->numberOrNull($childPayload['child_luas_da'] ?? null) ?? $changedDa;

                $tanah->children()->create([
                    'no_urut' => null,
                    'nop' => $tanah->nop,
                    'nop_raw' => $childPayload['child_nop'] ?: $tanah->nop_raw,
                    'nama_wajib_ipeda' => $childPayload['child_nama_wajib_ipeda'] ?: $validated['pemilik_baru'],
                    'tempat_tinggal' => $childPayload['child_tempat_tinggal'],
                    'nomor_persil' => $childPayload['child_nomor_persil'] ?: $this->makeChildPersil($tanah),
                    'kelas_desa' => $childPayload['child_kelas_desa'] ?: $tanah->kelas_desa,
                    'luas_ha' => $childHa,
                    'luas_da' => $childDa,
                    'luas_awal_ha' => $childHa,
                    'luas_awal_da' => $childDa,
                    'luas_sisa_ha' => $childHa,
                    'luas_sisa_da' => $childDa,
                    'ipeda_r' => $this->numberOrNull($childPayload['child_ipeda_r'] ?? null),
                    'ipeda_s' => $this->numberOrNull($childPayload['child_ipeda_s'] ?? null),
                    'sebab_perubahan' => $childPayload['child_sebab_perubahan'] ?: 'Hasil pembagian dari tanah ID ' . $tanah->id . ' / NOP ' . ($tanah->nop_raw ?: $tanah->nop ?: '-'),
                    'tgl_perubahan' => $childPayload['child_tgl_perubahan'] ?: ($validated['tanggal_perubahan'] ?? null),
                    'jenis_tanah' => $childPayload['child_jenis_tanah'] ?: $tanah->jenis_tanah,
                    'blok_id' => $childPayload['child_blok_id'] ?: $tanah->blok_id,
                    'created_by' => Auth::id(),
                ]);
            }

            return [
                'history' => $history,
                'tanah' => $tanah->fresh(['blok', 'histories', 'children.blok']),
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Riwayat perubahan tanah berhasil dicatat.',
                'history' => $this->serializeHistory($result['history']),
                'histories' => $result['tanah']->histories->map(fn ($history) => $this->serializeHistory($history)),
                'tanah' => $this->serializeTanah($result['tanah']),
            ], 201);
        }

        return Redirect::back()->with('success', 'Riwayat perubahan tanah berhasil dicatat.');
    }

    private function validatePartialSale(?float $basisHa, ?float $basisDa, ?float $changedHa, ?float $changedDa, array $payload): array
    {
        $changedHa = $changedHa ?? 0.0;
        $changedDa = $changedDa ?? 0.0;

        if ($changedHa <= 0 && $changedDa <= 0) {
            throw ValidationException::withMessages([
                'luas_berubah' => 'Luas berubah harus lebih besar dari 0 untuk perubahan dijual sebagian.',
            ]);
        }

        if ($changedHa > 0 && $basisHa === null) {
            throw ValidationException::withMessages([
                'luas_berubah' => 'Luas aktif saat ini (HA) belum tersedia.',
            ]);
        }

        if ($changedDa > 0 && $basisDa === null) {
            throw ValidationException::withMessages([
                'luas_berubah_da' => 'Luas aktif saat ini (DA) belum tersedia.',
            ]);
        }

        if ($basisHa !== null && $changedHa > $basisHa) {
            throw ValidationException::withMessages([
                'luas_berubah' => 'Luas berubah (HA) tidak boleh lebih besar dari luas sisa saat ini.',
            ]);
        }

        if ($basisDa !== null && $changedDa > $basisDa) {
            throw ValidationException::withMessages([
                'luas_berubah_da' => 'Luas berubah (DA) tidak boleh lebih besar dari luas sisa saat ini.',
            ]);
        }

        $newSisaHa = $basisHa !== null ? $basisHa - $changedHa : null;
        $newSisaDa = $basisDa !== null ? $basisDa - $changedDa : null;

        if ($newSisaHa !== null && $newSisaHa < 0) {
            throw ValidationException::withMessages(['luas_sisa' => 'Luas aktif (HA) tidak boleh minus.']);
        }

        if ($newSisaDa !== null && $newSisaDa < 0) {
            throw ValidationException::withMessages(['luas_sisa_da' => 'Luas aktif (DA) tidak boleh minus.']);
        }

        $manualSisaHa = $this->numberOrNull($payload['luas_sisa'] ?? null);
        $manualSisaDa = $this->numberOrNull($payload['luas_sisa_da'] ?? null);

        if ($manualSisaHa !== null && $newSisaHa !== null && ! $this->sameNumber($manualSisaHa, $newSisaHa)) {
            throw ValidationException::withMessages([
                'luas_sisa' => 'Luas aktif (HA) harus sama dengan luas aktif saat ini dikurangi luas berubah.',
            ]);
        }

        if ($manualSisaDa !== null && $newSisaDa !== null && ! $this->sameNumber($manualSisaDa, $newSisaDa)) {
            throw ValidationException::withMessages([
                'luas_sisa_da' => 'Luas aktif (DA) harus sama dengan luas aktif saat ini dikurangi luas berubah.',
            ]);
        }

        return [$newSisaHa, $newSisaDa];
    }

    private function makeChildPersil(Tanah $tanah): string
    {
        $base = $tanah->nomor_persil ?: 'PEC';

        for ($i = 1; $i <= 999; $i++) {
            $suffix = '-P' . $i;
            $candidate = mb_substr($base, 0, 50 - mb_strlen($suffix)) . $suffix;
            $exists = Tanah::query()
                ->where('blok_id', $tanah->blok_id)
                ->where('nomor_persil', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        return mb_substr($base, 0, 36) . '-P' . now()->format('YmdHis');
    }

    private function serializeTanah(Tanah $tanah): array
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
            'histories' => $tanah->histories->map(fn ($history) => $this->serializeHistory($history)),
            'children' => $tanah->children->map(fn ($child) => [
                'id' => $child->id,
                'nama_wajib_ipeda' => $child->nama_wajib_ipeda,
                'nop' => $child->nop,
                'nop_raw' => $child->nop_raw,
                'nomor_persil' => $child->nomor_persil,
                'blok' => $child->blok?->nama_blok,
                'luas_ha' => $child->luas_ha,
                'luas_da' => $child->luas_da,
                'luas_awal_ha' => $child->luas_awal_ha,
                'luas_awal_da' => $child->luas_awal_da,
                'luas_sisa_ha' => $child->luas_sisa_ha,
                'luas_sisa_da' => $child->luas_sisa_da,
                'tgl_perubahan' => $child->tgl_perubahan?->format('Y-m-d'),
            ]),
        ];
    }

    private function serializeHistory($history): array
    {
        return [
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
        ];
    }

    private function isPartialSale(string $value): bool
    {
        return mb_strtolower(trim($value)) === 'dijual sebagian';
    }

    private function isOwnerChange(string $value): bool
    {
        return mb_strtolower(trim($value)) === 'ganti pemilik';
    }

    private function extractChildPayload(array &$validated): array
    {
        $keys = [
            'child_nop',
            'child_nama_wajib_ipeda',
            'child_tempat_tinggal',
            'child_jenis_tanah',
            'child_blok_id',
            'child_nomor_persil',
            'child_kelas_desa',
            'child_luas_ha',
            'child_luas_da',
            'child_ipeda_r',
            'child_ipeda_s',
            'child_sebab_perubahan',
            'child_tgl_perubahan',
        ];

        $payload = [];

        foreach ($keys as $key) {
            $payload[$key] = $validated[$key] ?? null;
            unset($validated[$key]);
        }

        return $payload;
    }

    private function validateChildPersil(Tanah $tanah, array $childPayload): void
    {
        if (empty($childPayload['child_nomor_persil'])) {
            return;
        }

        $exists = Tanah::query()
            ->where('blok_id', $childPayload['child_blok_id'] ?: $tanah->blok_id)
            ->where('nomor_persil', $childPayload['child_nomor_persil'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'child_nomor_persil' => 'Nomor persil data hasil pembagian sudah digunakan pada blok ini.',
            ]);
        }
    }

    private function numberOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function sameNumber(float $left, float $right): bool
    {
        return abs($left - $right) < 0.0001;
    }
}
