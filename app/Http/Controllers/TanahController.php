<?php

namespace App\Http\Controllers;

use App\Imports\TanahImport;
use App\Models\Tanah;
use App\Models\MapBlok;
use App\Models\SismiopData;
use App\Support\NopNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class TanahController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tanahQuery = Tanah::with(['blok:id,nama_blok', 'histories', 'children.blok:id,nama_blok']);

        if ($search) {
            $tanahQuery->where(function ($query) use ($search) {
                $query->where('nop', 'like', "%{$search}%")
                    ->orWhere('nop_raw', 'like', "%{$search}%")
                    ->orWhere('nama_wajib_ipeda', 'like', "%{$search}%")
                    ->orWhere('nomor_persil', 'like', "%{$search}%")
                    ->orWhere('jenis_tanah', 'like', "%{$search}%")
                    ->orWhereHas('blok', function ($q) use ($search) {
                        $q->where('nama_blok', 'like', "%{$search}%");
                    });
            });
        }

        $tanah = $tanahQuery
            ->orderBy('nama_wajib_ipeda', 'asc')
            ->paginate(5)
            ->withQueryString();

        $blokList = MapBlok::orderBy('nama_blok')->get(['id', 'nama_blok']);

        return Inertia::render('Tanah/Index', [
            'tanah' => $tanah,
            'filters' => [
                'search' => $search,
            ],
            'blokList' => $blokList,
            'blokCount' => $blokList->count(),
            'openCreateModal' => false,
            'initialCreateNop' => null,
            'createPrefill' => null,
            'openEditModal' => false,
            'editTanahId' => null,
            'editTanahData' => null,
        ]);
    }

    public function create(Request $request)
    {
        $search = $request->input('search');
        $tanahQuery = Tanah::with(['blok:id,nama_blok', 'histories', 'children.blok:id,nama_blok']);

        if ($search) {
            $tanahQuery->where(function ($query) use ($search) {
                $query->where('nop', 'like', "%{$search}%")
                    ->orWhere('nop_raw', 'like', "%{$search}%")
                    ->orWhere('nama_wajib_ipeda', 'like', "%{$search}%")
                    ->orWhere('nomor_persil', 'like', "%{$search}%")
                    ->orWhere('jenis_tanah', 'like', "%{$search}%")
                    ->orWhereHas('blok', function ($q) use ($search) {
                        $q->where('nama_blok', 'like', "%{$search}%");
                    });
            });
        }

        $tanah = $tanahQuery
            ->orderBy('nama_wajib_ipeda', 'asc')
            ->paginate(5)
            ->withQueryString();

        $initialNopRaw = $request->query('nop');
        $normalizedNop = NopNormalizer::normalize($initialNopRaw);
        $source = (string) $request->query('source', '');
        $prefill = null;

        if ($source === 'sismiop' && $normalizedNop) {
            $sismiop = SismiopData::query()->where('nop', $normalizedNop)->first();
            $parsed = NopNormalizer::parse($normalizedNop);

            if ($sismiop) {
                $prefill = [
                    'source' => 'sismiop',
                    'nop' => $normalizedNop,
                    'nop_raw' => $sismiop->nop_raw ?? $initialNopRaw,
                    'nama_wajib_ipeda' => $sismiop->subjek_pajak_nama_wajib_pajak,
                    'tempat_tinggal' => $sismiop->subjek_pajak_jalan_dusun,
                    'luas_ha' => $sismiop->bumi,
                    'blok' => $parsed['blok'] ?? null,
                ];
            }
        }

        $blokList = MapBlok::orderBy('nama_blok')->get(['id', 'nama_blok']);

        return Inertia::render('Tanah/Index', [
            'tanah' => $tanah,
            'filters' => [
                'search' => $search,
            ],
            'blokList' => $blokList,
            'blokCount' => $blokList->count(),
            'openCreateModal' => true,
            'initialCreateNop' => $normalizedNop,
            'createPrefill' => $prefill,
            'openEditModal' => false,
            'editTanahId' => null,
            'editTanahData' => null,
        ]);
    }

    public function edit(Request $request, Tanah $tanah)
    {
        $search = $request->input('search');
        $tanahQuery = Tanah::with(['blok:id,nama_blok', 'histories', 'children.blok:id,nama_blok']);

        if ($search) {
            $tanahQuery->where(function ($query) use ($search) {
                $query->where('nop', 'like', "%{$search}%")
                    ->orWhere('nop_raw', 'like', "%{$search}%")
                    ->orWhere('nama_wajib_ipeda', 'like', "%{$search}%")
                    ->orWhere('nomor_persil', 'like', "%{$search}%")
                    ->orWhere('jenis_tanah', 'like', "%{$search}%")
                    ->orWhereHas('blok', function ($q) use ($search) {
                        $q->where('nama_blok', 'like', "%{$search}%");
                    });
            });
        }

        $list = $tanahQuery
            ->orderBy('nama_wajib_ipeda', 'asc')
            ->paginate(5)
            ->withQueryString();

        $blokList = MapBlok::orderBy('nama_blok')->get(['id', 'nama_blok']);

        $editTanahData = [
            'id' => $tanah->id,
            'parent_id' => $tanah->parent_id,
            'no_urut' => $tanah->no_urut,
            'nop' => $tanah->nop,
            'nop_raw' => $tanah->nop_raw,
            'nama_wajib_ipeda' => $tanah->nama_wajib_ipeda,
            'tempat_tinggal' => $tanah->tempat_tinggal,
            'nomor_persil' => $tanah->nomor_persil,
            'blok_id' => $tanah->blok_id,
            'jenis_tanah' => $tanah->jenis_tanah,
            'luas_ha' => $tanah->luas_ha,
            'luas_da' => $tanah->luas_da,
            'ipeda_r' => $tanah->ipeda_r,
            'ipeda_s' => $tanah->ipeda_s,
            'luas_awal_ha' => $tanah->luas_awal_ha,
            'luas_awal_da' => $tanah->luas_awal_da,
            'luas_sisa_ha' => $tanah->luas_sisa_ha,
            'luas_sisa_da' => $tanah->luas_sisa_da,
            'sebab_perubahan' => $tanah->sebab_perubahan,
            'tgl_perubahan' => $tanah->tgl_perubahan?->format('Y-m-d'),
            'histories' => $tanah->histories()->get()->map(fn ($history) => [
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
            'children' => $tanah->children()->with('blok:id,nama_blok')->get()->map(fn ($child) => [
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

        return Inertia::render('Tanah/Index', [
            'tanah' => $list,
            'filters' => [
                'search' => $search,
            ],
            'blokList' => $blokList,
            'blokCount' => $blokList->count(),
            'openCreateModal' => false,
            'initialCreateNop' => null,
            'createPrefill' => null,
            'openEditModal' => true,
            'editTanahId' => $tanah->id,
            'editTanahData' => $editTanahData,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->manualRules());
            $validated = $this->normalizeNopPayload($validated);
            $validated = $this->withInitialLuasTracking($validated);

            Tanah::create($validated);

            return Redirect::route('tanah.index')
                ->with('success', 'Data Tanah berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, Tanah $tanah)
    {
        try {
            $validated = $request->validate($this->manualRules($tanah->id, (int) $request->input('blok_id')));
            $validated = $this->normalizeNopPayload($validated);
            $validated = $this->withUpdatedLuasTracking($validated, $tanah);

            $tanah->update($validated);

            return Redirect::route('tanah.index')
                ->with('success', 'Data Tanah berhasil diupdate.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $tanah = Tanah::findOrFail($id);
            $message = 'Data ini memiliki riwayat atau data hasil pembagian, sehingga tidak bisa dihapus langsung.';

            if ($tanah->parent_id !== null || $tanah->histories()->exists() || $tanah->children()->exists()) {
                if (request()->expectsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return redirect()->back()->withErrors([
                    'error' => $message,
                ]);
            }

            $tanah->delete();
            return Redirect::route('tanah.index')
                ->with('success', 'Data Tanah berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }

    }

    public function importForm()
    {
        $preview = session('tanah_import_preview');

        return Inertia::render('Tanah/ImportExcel', [
            'blokCount' => MapBlok::count(),
            'preview' => $preview ?? [
                'errors' => [],
                'validCount' => 0,
                'errorCount' => 0,
                'canImport' => false,
                'fileName' => null,
                'id' => null,
            ],
        ]);
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:10240',
        ]);

        $import = new TanahImport;

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['error' => 'Gagal membaca file.']);
        }

        $rows = collect($import->rows());


        if ($rows->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada data pada file yang diunggah.']);
        }

        $blokMap = MapBlok::pluck('id', 'nama_blok')
            ->mapWithKeys(function ($id, $name) {
                return [Str::upper(trim($name)) => $id];
            });

        // Fetch semua kombinasi blok_id|nomor_persil yang sudah ada untuk avoid N+1 query
        $existingCombinations = Tanah::select('blok_id', 'nomor_persil')
            ->get()
            ->mapWithKeys(function ($tanah) {
                return [$tanah->blok_id . '|' . strtolower($tanah->nomor_persil) => true];
            });

        $errors = [];
        $validRows = [];
        $seenCombination = [];
        $rowRules = $this->importRowRules();

        foreach ($rows as $row) {
            $rowNumber = $row['row_number'] ?? null;
            $rowData = collect($row)->except('row_number')->toArray();
            $rowData['jenis_tanah'] = $rowData['jenis_tanah'] ? strtolower($rowData['jenis_tanah']) : null;

            $validator = Validator::make($rowData, $rowRules);

            if ($validator->fails()) {
                foreach ($validator->errors()->getMessages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'field' => $field,
                            'message' => $message,
                        ];
                    }
                }
                continue;
            }

            $blokKey = Str::upper(trim($rowData['blok']));
            $blokId = $blokMap[$blokKey] ?? null;

            if (!$blokId) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'blok',
                    'message' => "Blok '{$rowData['blok']}' belum diupload.",
                ];
                continue;
            }

            $combinationKey = $blokId . '|' . strtolower($rowData['nomor_persil']);

            if (isset($seenCombination[$combinationKey])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'nomor_persil',
                    'message' => 'Nomor persil duplikat pada file untuk blok tersebut.',
                ];
                continue;
            }

            if (isset($existingCombinations[$combinationKey])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'nomor_persil',
                    'message' => 'Nomor persil sudah terdaftar untuk blok tersebut.',
                ];
                continue;
            }

            $seenCombination[$combinationKey] = true;
            $validRows[] = $this->mapRowToPayload($rowData, $blokId);
        }

        $canImport = empty($errors) && !empty($validRows);
        $previewId = $canImport ? (string) Str::uuid() : null;

        if ($canImport) {
            $userId = Auth::id();
            Cache::put($this->previewCacheKey($previewId, $userId), $validRows, now()->addMinutes(30));
        }

        session()->flash('tanah_import_preview', [
            'id' => $previewId,
            'fileName' => $request->file('file')->getClientOriginalName(),
            'validCount' => count($validRows),
            'errorCount' => count($errors),
            'errors' => $errors,
            'canImport' => $canImport,
        ]);

        return redirect()->route('tanah.import.form')
            ->with($canImport ? 'success' : 'error', $canImport ? 'File siap diimport.' : 'Ditemukan beberapa kesalahan.');
    }

    public function importExecute(Request $request)
    {
        $request->validate([
            'preview_id' => 'required|string',
        ]);

        $userId = Auth::id();
        $cacheKey = $this->previewCacheKey($request->input('preview_id'), $userId);
        $rows = Cache::pull($cacheKey);

        if (!$rows) {
            return back()->withErrors(['error' => 'Data preview tidak ditemukan atau sudah kadaluarsa.']);
        }

        DB::beginTransaction();

        try {
            $now = now();
            foreach ($rows as &$row) {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
            }
            Tanah::insert($rows);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e->getMessage());
            return back()->withErrors(['error' => 'Gagal mengimpor data. Silakan coba lagi atau hubungi administrator.']);
        }

        return Redirect::route('tanah.index')->with('success', 'Data Tanah berhasil diimport.');
    }

    private function manualRules(?int $tanahId = null, ?int $blokId = null): array
    {
        $blokId = $blokId ?? request()->input('blok_id');

        $uniqueRule = Rule::unique('tanah')->where(function ($query) use ($blokId) {
            if ($blokId) {
                $query->where('blok_id', $blokId);
            }

            return $query;
        });

        if ($tanahId) {
            $uniqueRule->ignore($tanahId);
        }

        return [
            'no_urut' => 'nullable|string|max:10',
            'parent_id' => 'nullable|exists:tanah,id',
            'nop' => [
                'nullable',
                'string',
                'max:64',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    if (NopNormalizer::normalize((string) $value) === null) {
                        $fail('NOP harus mengandung angka yang valid.');
                    }
                },
            ],
            'nama_wajib_ipeda' => 'required|string|max:255',
            'tempat_tinggal' => 'nullable|string|max:255',
            'nomor_persil' => [
                'required',
                'string',
                'max:50',
                $uniqueRule,
            ],
            'kelas_desa' => 'nullable|string|max:50',
            'luas_ha' => 'nullable|numeric|gt:0',
            'luas_da' => 'nullable|numeric|gt:0',
            'luas_awal_ha' => 'nullable|numeric|gt:0',
            'luas_awal_da' => 'nullable|numeric|gt:0',
            'luas_sisa_ha' => 'nullable|numeric|gt:0',
            'luas_sisa_da' => 'nullable|numeric|gt:0',
            'ipeda_r' => 'nullable|numeric|gt:0',
            'ipeda_s' => 'nullable|numeric|gt:0',
            'jenis_tanah' => 'required|in:basah,kering',
            'sebab_perubahan' => 'nullable|string',
            'tgl_perubahan' => 'nullable|date',
            'blok_id' => 'required|exists:map_blok,id',
        ];
    }

    private function importRowRules(): array
    {
        return [
            'no_urut' => 'nullable|string|max:10',
            'nop' => 'nullable|string|max:64',
            'nama_wajib_ipeda' => 'required|string|max:255',
            'tempat_tinggal' => 'nullable|string|max:255',
            'nomor_persil' => 'required|string|max:50',
            'kelas_desa' => 'nullable|string|max:50',
            'luas_ha' => 'nullable|numeric|gt:0',
            'luas_da' => 'nullable|numeric|gt:0',
            'luas_awal_ha' => 'nullable|numeric|gt:0',
            'luas_awal_da' => 'nullable|numeric|gt:0',
            'luas_sisa_ha' => 'nullable|numeric|gt:0',
            'luas_sisa_da' => 'nullable|numeric|gt:0',
            'ipeda_r' => 'nullable|numeric|gt:0',
            'ipeda_s' => 'nullable|numeric|gt:0',
            'jenis_tanah' => 'required|in:basah,kering',
            'sebab_perubahan' => 'nullable|string',
            'tgl_perubahan' => 'nullable|date',
            'blok' => 'required|string|max:50',
        ];
    }

    private function mapRowToPayload(array $rowData, int $blokId): array
    {
        return [
            'no_urut' => $rowData['no_urut'] ?? null,
            ...$this->normalizeNopPayload([
                'nop' => $rowData['nop'] ?? null,
            ]),
            'nama_wajib_ipeda' => $rowData['nama_wajib_ipeda'],
            'tempat_tinggal' => $rowData['tempat_tinggal'] ?? null,
            'nomor_persil' => $rowData['nomor_persil'],
            'kelas_desa' => $rowData['kelas_desa'] ?? null,
            'luas_ha' => $rowData['luas_ha'] ?? null,
            'luas_da' => $rowData['luas_da'] ?? null,
            'luas_awal_ha' => $rowData['luas_ha'] ?? null,
            'luas_awal_da' => $rowData['luas_da'] ?? null,
            'luas_sisa_ha' => $rowData['luas_ha'] ?? null,
            'luas_sisa_da' => $rowData['luas_da'] ?? null,
            'ipeda_r' => $rowData['ipeda_r'] ?? null,
            'ipeda_s' => $rowData['ipeda_s'] ?? null,
            'jenis_tanah' => strtolower($rowData['jenis_tanah']),
            'sebab_perubahan' => $rowData['sebab_perubahan'] ?? null,
            'tgl_perubahan' => $rowData['tgl_perubahan'] ?? null,
            'blok_id' => $blokId,
        ];
    }

    private function previewCacheKey(string $previewId, ?int $userId): string
    {
        return "tanah-import-{$userId}-{$previewId}";
    }

    private function normalizeNopPayload(array $payload): array
    {
        $raw = $payload['nop'] ?? null;
        $normalized = NopNormalizer::normalize($raw);

        $payload['nop'] = $normalized;
        $payload['nop_raw'] = $raw !== null && trim((string) $raw) !== '' ? trim((string) $raw) : null;

        return $payload;
    }

    private function withInitialLuasTracking(array $payload): array
    {
        $payload['luas_awal_ha'] = $payload['luas_awal_ha'] ?? $payload['luas_ha'] ?? null;
        $payload['luas_awal_da'] = $payload['luas_awal_da'] ?? $payload['luas_da'] ?? null;
        $payload['luas_sisa_ha'] = $payload['luas_sisa_ha'] ?? $payload['luas_ha'] ?? null;
        $payload['luas_sisa_da'] = $payload['luas_sisa_da'] ?? $payload['luas_da'] ?? null;

        return $payload;
    }

    private function withUpdatedLuasTracking(array $payload, Tanah $tanah): array
    {
        $hasLuasChange = (array_key_exists('luas_ha', $payload) && (string) $payload['luas_ha'] !== (string) $tanah->luas_ha)
            || (array_key_exists('luas_da', $payload) && (string) $payload['luas_da'] !== (string) $tanah->luas_da);

        if ($hasLuasChange && ($tanah->histories()->exists() || $tanah->children()->exists())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'luas_ha' => 'Data ini sudah memiliki riwayat atau hasil pembagian. Luas hanya dapat diubah melalui Catat Perubahan.',
            ]);
        }

        if (array_key_exists('luas_ha', $payload)) {
            $payload['luas_sisa_ha'] = $payload['luas_ha'];
            $payload['luas_awal_ha'] = $payload['luas_ha'];
        }

        if (array_key_exists('luas_da', $payload)) {
            $payload['luas_sisa_da'] = $payload['luas_da'];
            $payload['luas_awal_da'] = $payload['luas_da'];
        }

        return $payload;
    }
}
