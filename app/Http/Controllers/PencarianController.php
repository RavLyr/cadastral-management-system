<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PencarianController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Tanah::with(['blok']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_wajib_ipeda', 'like', "%{$search}%")
                  ->orWhere('nomor_persil', 'like', "%{$search}%")
                  ->orWhereHas('blok', function ($q2) use ($search) {
                      $q2->where('nama_blok', 'like', "%{$search}%");
                  });
            });
        }

        $results = $query->orderBy('nama_wajib_ipeda')
            ->paginate(5)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_wajib_ipeda' => $item->nama_wajib_ipeda,
                    'nomor_persil' => $item->nomor_persil,
                    'blok' => $item->blok?->nama_blok,
                    'luas_m2' => $item->luas_ha ? $item->luas_ha * 10000 : null,
                    'skala' => $item->blok?->skala,
                    'peta_url' => $item->blok?->file_pdf ? asset('storage/' . $item->blok->file_pdf) : null,
                    'tempat_tinggal' => $item->tempat_tinggal,
                    'ipeda_r' => $item->ipeda_r,
                    'ipeda_s' => $item->ipeda_s,
                    'sebab_perubahan' => $item->sebab_perubahan,
                    'tgl_perubahan' => $item->tgl_perubahan,
                    'jenis_tanah' => $item->jenis_tanah,
                ];
            });

        return Inertia::render('Pencarian/Index', [
            'results' => $results,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
