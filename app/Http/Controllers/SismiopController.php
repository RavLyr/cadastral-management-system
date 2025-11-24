<?php

namespace App\Http\Controllers;

use App\Imports\SismiopImport;
use App\Models\MapBlok;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SismiopData;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;

class SismiopController extends Controller
{
    public function index(Request $request)
    {
        $query = SismiopData::query();

        if ($request->search) {
            $query->where('nop', 'like', "%{$request->search}%")
                  ->orWhere('subjek_pajak_nama_wajib_pajak', 'like', "%{$request->search}%");
        }

        $existingData = $query->paginate(10)->withQueryString();
        $importedData = session('imported_data', []);
        return Inertia::render('Sismiop/Index', [
            'existingData' => $existingData,
            'importedData' => $importedData,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:5120',
        ]);

        try {
            $import = new SismiopImport;
            Excel::import($import, $request->file('file'));

            session(['imported_data' => $import->data]);

            return redirect()->route('sismiop.index')->with('success', 'Data SISMIOP berhasil dipreview.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal import: ' . $e->getMessage()]);
        }
    }
    public function update(Request $request, $id)
    {
        $sismiop = SismiopData::findOrFail($id);
        $validated = $request->validate([
            'objek_pajak_jalan_dusun_op' => 'nullable|string|max:255',
            'objek_pajak_rt' => 'nullable|string|max:10',
            'objek_pajak_rw' => 'nullable|string|max:10',
            'objek_pajak_desa' => 'nullable|string|max:100',
            'subjek_pajak_nama_wajib_pajak' => 'required|string|max:255',
            'subjek_pajak_jalan_dusun' => 'nullable|string|max:255',
            'subjek_pajak_rt' => 'nullable|string|max:10',
            'subjek_pajak_rw' => 'nullable|string|max:10',
            'subjek_pajak_desa_kel' => 'nullable|string|max:100',
            'subjek_pajak_kabupaten_kota' => 'nullable|string|max:100',
            'bumi' => 'nullable|numeric|min:0',
            'bng' => 'nullable|numeric|min:0',
            'jns_bumi' => 'nullable|string|max:50',
            'usulan_pembetulan' => 'nullable|string|max:255',
            'blok' => 'nullable|string|max:50',
            'no_urut' => 'nullable|string|max:20',
            'map_blok_id' => 'nullable|exists:map_blok,id',
        ]);
        try {
            $sismiop->update($validated);

            return Redirect::route('sismiop.index')->with('success', 'Data SISMIOP berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal memperbarui data: ' . $e->getMessage()]);
        }
    }

    public function commit(Request $request)
    {
        $importedData = session('imported_data', []);

        if (empty($importedData)) {
            return back()->withErrors(['error' => 'Tidak ada data untuk disimpan.']);
        }

        try {
            foreach ($importedData as $data) {
                SismiopData::create($data);
            }

            session()->forget('imported_data');

            return redirect()->route('sismiop.index')->with('success', 'Data SISMIOP berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    public function clear()
    {
        SismiopData::truncate();
        session()->forget('imported_data');
        return redirect()->route('sismiop.index')->with('success', 'Data import berhasil dihapus.');
    }

    public function destroy($id)
    {
        $data = SismiopData::findOrFail($id);
        $data->delete();

        return redirect()->route('sismiop.index')->with('success', 'Data SISMIOP berhasil dihapus.');
    }

    public function edit($id)
    {
        $row = SismiopData::with('mapBlok')->findOrFail($id);
        $mapBloks = MapBlok::all(['id', 'nama_blok']);

        return Inertia::render('Sismiop/Edit', [
            'row' => $row,
            'mapBloks' => $mapBloks,
        ]);
    }
}
