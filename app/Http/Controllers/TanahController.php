<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use App\Models\MapBlok;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

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

        return Inertia::render('Tanah/Index', [
            'tanah' => $tanah,
            'search' => $search,
            'blok' => MapBlok::all(['id', 'nama_blok']),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'no_urut' => 'nullable|string|max:20',
                'nama_wajib_ipeda' => 'required|string|max:255',
                'tempat_tinggal' => 'nullable|string',
                'nomor_persil' => 'required|string|max:50',
                'kelas_desa' => 'nullable|string|max:50',
                'luas_ha' => 'nullable|numeric|min:0',
                'luas_da' => 'nullable|numeric|min:0',
                'ipeda_r' => 'nullable|numeric|min:0',
                'ipeda_s' => 'nullable|numeric|min:0',
                'sebab_perubahan' => 'nullable|string',
                'tgl_perubahan' => 'nullable|date',
                'jenis_tanah' => 'required|in:basah,kering',
                'blok_id' => 'required|exists:map_blok,id',
                'nomor_persil' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tanah')->where(function ($query) use ($request) {
                        return $query->where('blok_id', $request->blok_id);
                    }),
                ]
            ]);

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
            $validated = $request->validate([
                'no_urut' => 'nullable|string|max:20',
                'nama_wajib_ipeda' => 'required|string|max:255',
                'tempat_tinggal' => 'nullable|string',
                'nomor_persil' => 'required|string|max:50',
                'kelas_desa' => 'nullable|string|max:50',
                'luas_ha' => 'nullable|numeric|min:0',
                'luas_da' => 'nullable|numeric|min:0',
                'ipeda_r' => 'nullable|numeric|min:0',
                'ipeda_s' => 'nullable|numeric|min:0',
                'sebab_perubahan' => 'nullable|string',
                'tgl_perubahan' => 'nullable|date',
                'jenis_tanah' => 'required|in:basah,kering',
                'blok_id' => 'required|exists:map_blok,id',
                'nomor_persil' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('tanah')->where(function ($query) use ($request) {
                        return $query->where('blok_id', $request->blok_id);
                    })->ignore($tanah->id),
                ]
            ]);

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
}
