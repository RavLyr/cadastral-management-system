<?php

namespace App\Http\Controllers;

use App\Imports\TanahImport;
use App\Models\Tanah;
use App\Models\MapBlok;
use Illuminate\Http\Request;
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
        $tanahQuery = Tanah::with(['blok:id,nama_blok']);

        if ($search) {
            $tanahQuery->where(function ($query) use ($search) {
                $query->where('nama_wajib_ipeda', 'like', "%{$search}%")
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
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate($this->manualRules());

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
            $tanah->delete();
            return Redirect::route('tanah.index')
                ->with('success', 'Data Tanah berhasil dihapus.');
        } catch (\Exception $e) {
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

            $combinationKey = $blokId . '|' . Str::lower($rowData['nomor_persil']);

            if (isset($seenCombination[$combinationKey])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'nomor_persil',
                    'message' => 'Nomor persil duplikat pada file untuk blok tersebut.',
                ];
                continue;
            }

            $exists = Tanah::where('blok_id', $blokId)
                ->where('nomor_persil', $rowData['nomor_persil'])
                ->exists();

            if ($exists) {
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
            Cache::put($this->previewCacheKey($previewId), $validRows, now()->addMinutes(30));
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

        $cacheKey = $this->previewCacheKey($request->input('preview_id'));
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
            'nama_wajib_ipeda' => 'required|string|max:255',
            'tempat_tinggal' => 'nullable|string|max:255',
            'nomor_persil' => [
                'required',
                'string',
                'max:50',
                $uniqueRule,
            ],
            'kelas_desa' => 'nullable|string|max:50',
            'luas_ha' => 'nullable|numeric',
            'luas_da' => 'nullable|numeric',
            'ipeda_r' => 'nullable|numeric',
            'ipeda_s' => 'nullable|numeric',
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
            'nama_wajib_ipeda' => 'required|string|max:255',
            'tempat_tinggal' => 'nullable|string|max:255',
            'nomor_persil' => 'required|string|max:50',
            'kelas_desa' => 'nullable|string|max:50',
            'luas_ha' => 'nullable|numeric',
            'luas_da' => 'nullable|numeric',
            'ipeda_r' => 'nullable|numeric',
            'ipeda_s' => 'nullable|numeric',
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
            'nama_wajib_ipeda' => $rowData['nama_wajib_ipeda'],
            'tempat_tinggal' => $rowData['tempat_tinggal'] ?? null,
            'nomor_persil' => $rowData['nomor_persil'],
            'kelas_desa' => $rowData['kelas_desa'] ?? null,
            'luas_ha' => $rowData['luas_ha'] ?? null,
            'luas_da' => $rowData['luas_da'] ?? null,
            'ipeda_r' => $rowData['ipeda_r'] ?? null,
            'ipeda_s' => $rowData['ipeda_s'] ?? null,
            'jenis_tanah' => strtolower($rowData['jenis_tanah']),
            'sebab_perubahan' => $rowData['sebab_perubahan'] ?? null,
            'tgl_perubahan' => $rowData['tgl_perubahan'] ?? null,
            'blok_id' => $blokId,
        ];
    }

    private function previewCacheKey(string $previewId): string
    {
        return "tanah-import-{$previewId}";
    }
}
