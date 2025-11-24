<?php

namespace App\Http\Controllers;

use App\Models\Tanah;
use Illuminate\Http\Request;
use \setasign\Fpdi\Tcpdf\Fpdi;

class PrintController extends Controller
{
    public function generate($id)
    {
        // Fetch tanah with blok relation
        $tanah = Tanah::with('blok')->findOrFail($id);

        // Create new PDF document
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Buku C Digital');
        $pdf->SetAuthor('System');
        $pdf->SetTitle('Data Tanah - ' . $tanah->nama_wajib_ipeda);
        $pdf->SetSubject('Data Tanah dan Peta');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $this->addDataPage($pdf, $tanah);

        $pdf->AddPage('P', array(210, 315));

        $this->addMapPage($pdf, $tanah);

        $pdf->Output('data_tanah_' . $tanah->nama_wajib_ipeda . '.pdf', 'I');
    }

    private function addDataPage(Fpdi $pdf, $tanah)
    {
        $this->addHeader($pdf, $tanah);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'DATA TANAH', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 12);

        $data = [
            ['No. Urut', $tanah->no_urut ?? '-'],
            ['Nama Wajib Pajak', $tanah->nama_wajib_ipeda],
            ['Tempat Tinggal', $tanah->tempat_tinggal ?? '-'],
            ['Nomor Persil', $tanah->nomor_persil],
            ['Luas (Ha)', $tanah->luas_ha ? number_format($tanah->luas_ha, 2) : '-'],
            ['Luas (Da)', $tanah->luas_da ? number_format($tanah->luas_da, 2) : '-'],
            ['IPEDA R (Rp)', $tanah->ipeda_r ? number_format($tanah->ipeda_r, 2) : '-'],
            ['IPEDA S (Sen)', $tanah->ipeda_s ? number_format($tanah->ipeda_s, 2) : '-'],
            ['Sebab Perubahan', $tanah->sebab_perubahan ?? '-'],
            ['Tanggal Perubahan', $tanah->tgl_perubahan ? $tanah->tgl_perubahan->format('d/m/Y') : '-'],
            ['Jenis Tanah', ucfirst($tanah->jenis_tanah)],
            ['Blok', $tanah->blok->nama_blok ?? '-'],
        ];

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(0, 0, 0);

        foreach ($data as $index => $row) {
            $fill = $index % 2 == 0;
            $pdf->Cell(60, 8, $row[0], 1, 0, 'L', $fill);
            $pdf->Cell(0, 8, $row[1], 1, 1, 'L', $fill);
        }
    }
    private function addHeader(Fpdi $pdf, $tanah)
    {
        // Nama Aplikasi
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(0, 8);
        $pdf->Cell(0, 8, 'BUKU C DIGITAL - SISTEM INFORMASI TANAH', 0, 1, 'C');

        // Nama Desa (static / bisa dari .env)
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'DESA TEMUREJO - KECAMATAN KARANGRAYUNG', 0, 1, 'C');

        // Tanggal Cetak
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Tanggal Cetak: ' . now()->format('d/m/Y'), 0, 1, 'C');

        // Garis pemisah
        $pdf->SetLineWidth(0.4);
        $pdf->Line(10, 28, 200, 28);

        $pdf->Ln(8);
    }


    private function addMapPage(Fpdi $pdf, $tanah)
    {
        $mapPath = storage_path('app/public/' . $tanah->blok->file_pdf);

        if (file_exists($mapPath)) {
            if (pathinfo($mapPath, PATHINFO_EXTENSION) === 'pdf') {
                $pageCount = $pdf->setSourceFile($mapPath);
                $tplIdx = $pdf->importPage(1); // Halaman pertama
                $pdf->useTemplate($tplIdx, 0, 0, 210, 315); // Mulai dari 0,0 - ukuran eksak 210x315mm
            } else {
                $pdf->Image($mapPath, 0, 0, 210, 315, '', '', '', false, 300, '', false, false, 0);
            }
        } else {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'File peta tidak ditemukan: ' . $mapPath, 0, 1, 'C');
        }

        // Add title below the map
        $pdf->SetY(320); // Below 315mm map
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'PETA PERSIL', 0, 1, 'C');

        // Add scale bar at bottom
        $this->addScaleBar($pdf, $tanah->blok->skala);
    }

    private function addScaleBar(Fpdi $pdf, $skala)
    {
        $pdf->SetY(300); // Near bottom of 315mm page

        if (!$skala) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 5, 'Skala tidak tersedia', 0, 1, 'C');
            return;
        }

        $parts = explode(':', $skala);
        if (count($parts) != 2) {
            $pdf->Cell(0, 5, 'Format skala tidak valid: ' . $skala, 0, 1, 'C');
            return;
        }

        $scale = (int) $parts[1];

        $barLengthMm = 10;
        $realDistanceM = $barLengthMm * $scale / 1000;

        $startX = 50;
        $startY = $pdf->GetY();
        $barHeight = 3;

        $pdf->SetFillColor(0, 0, 0);
        $pdf->Rect($startX, $startY, $barLengthMm, $barHeight, 'F');

        $pdf->Rect($startX, $startY - 1, 0.5, $barHeight + 2, 'F');
        $pdf->Rect($startX + $barLengthMm, $startY - 1, 0.5, $barHeight + 2, 'F');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($startX, $startY + $barHeight + 2);
        $pdf->Cell($barLengthMm, 5, number_format($realDistanceM, 0) . ' m', 0, 0, 'C');

        $pdf->SetXY($startX, $startY - 8);
        $pdf->Cell($barLengthMm, 5, 'Skala ' . $skala, 0, 0, 'C');
    }
}
