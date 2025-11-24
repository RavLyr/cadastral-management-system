<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\MapBlok;

class PetaController extends Controller
{
    public function index()
    {
        $petaBlok = MapBlok::orderBy('nama_blok')
            ->select(['id', 'nama_blok', 'skala', 'file_pdf', 'created_at'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_blok' => $item->nama_blok,
                    'skala' => $item->skala,
                    'file_name' => basename($item->file_pdf),
                    'file_url' => Storage::url($item->file_pdf),
                    'created_at' => $item->created_at,
                ];
            });

        return Inertia::render('Peta/Index', [
            'petaBlok' => $petaBlok,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([

            'nama_blok' => 'required|string|max:50|unique:map_blok,nama_blok',
            'skala' => [
                'required',
                'string',
                'max:20',
                'regex:/^1:\d+$/'
            ],
            'file' => 'required|file|mimes:pdf|max:10240',
            'deskripsi' => 'nullable|string',
        ], [
            'skala.regex' => 'Skala harus dalam format 1:xxxx (contoh: 1:1000)',
        ]);

        try {
            $filename = str_replace(' ', '_', strtolower($validated['nama_blok'])) . '.pdf';
            // $filename = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('peta-blok', $filename, 'public');


            MapBlok::create([
                'nama_blok' => $validated['nama_blok'],
                'skala' => $validated['skala'],
                'file_pdf' => $path,
                'deskripsi' => $validated['deskripsi'] ?? null,
            ]);

            return redirect()->route('peta.index')->with('success', 'Peta blok berhasil diunggah.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengunggah peta blok: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $blok = MapBlok::findOrFail($id);

        try {
            Storage::disk('public')->delete($blok->file_pdf);
            $blok->delete();

            return redirect()->route('peta.index')->with('success', 'Peta blok berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus peta blok: ' . $e->getMessage()]);
        }
    }
}
