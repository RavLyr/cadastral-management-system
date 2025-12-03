<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    /**
     * Download template file
     *
     * @param string $template Template identifier (tanah, sismiop, etc)
     * @return BinaryFileResponse
     */
    public function download(string $template)
    {
        $templates = [
            'tanah' => 'Template Tanah DESA TEMUREJO.xlsx',
            'sismiop' => 'Template DHR DESA TEMUREJO.xlsx',
        ];

        if (!isset($templates[$template])) {
            abort(404, 'Template tidak ditemukan.');
        }

        $filename = $templates[$template];
        $filepath = public_path('templates/' . $filename);

        if (!file_exists($filepath)) {
            abort(404, 'File template tidak ditemukan.');
        }

        return response()->download($filepath, $filename);
    }
}
