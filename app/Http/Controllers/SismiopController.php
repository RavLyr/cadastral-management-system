<?php

namespace App\Http\Controllers;

use App\Imports\SismiopImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SismiopData;
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
}
