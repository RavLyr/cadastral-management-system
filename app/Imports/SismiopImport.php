<?php

namespace App\Imports;

use App\Support\NopNormalizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\SismiopData; // Import model
use App\Models\Tanah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SismiopImport implements ToCollection, WithStartRow
{
    /**
     * Flag to stop reading once the footer section is reached.
     */
    protected bool $skipRows = false;

    /**
     * Collected preview data.
     */
    public array $data = [];

    public array $report = [
        'total_row' => 0,
        'berhasil' => 0,
        'nop_kosong' => 0,
        'nop_tidak_valid_panjang' => 0,
        'match_gis' => 0,
        'tidak_match_gis' => 0,
        'sudah_ada_tanah' => 0,
        'belum_ada_tanah' => 0,
        'duplikat_nop' => 0,
    ];

    protected array $gisNops = [];
    protected array $tanahNops = [];
    protected bool $supportsNopRaw = false;

    public function __construct()
    {
        $this->supportsNopRaw = Schema::hasColumn('dhr_desa_temurejo', 'nop_raw');

        $this->gisNops = DB::table('gis_bidang_tanah')
            ->whereNotNull('nop')
            ->pluck('nop')
            ->map(fn ($nop) => (string) $nop)
            ->flip()
            ->all();

        $this->tanahNops = Tanah::query()
            ->whereNotNull('nop')
            ->where('nop', '!=', '')
            ->pluck('nop')
            ->map(fn ($nop) => (string) $nop)
            ->flip()
            ->all();
    }

    /**
     * Specify the starting row for the import.
     */
    public function startRow(): int
    {
        return 7; // Mulai dari baris ke-7
    }

    public function collection(Collection $rows): void
    {
        $seenNops = [];

        foreach ($rows as $row) {
            $row = $this->normalizeRow($row);
            $this->report['total_row']++;

            if ($this->rowContainsFooterSection($row)) {
                $this->skipRows = true;
                continue;
            }

            if ($this->skipRows) {
                continue;
            }

            $nopRaw = $this->extractValue($row, 1);
            $nop = NopNormalizer::normalize($nopRaw);

            if ($nop === null) {
                $this->report['nop_kosong']++;
                continue;
            }

            if (strlen($nop) !== 18) {
                $this->report['nop_tidak_valid_panjang']++;
            }

            if (isset($this->gisNops[$nop])) {
                $this->report['match_gis']++;
            } else {
                $this->report['tidak_match_gis']++;
            }

            if (isset($this->tanahNops[$nop])) {
                $this->report['sudah_ada_tanah']++;
            } else {
                $this->report['belum_ada_tanah']++;
            }

            if (isset($seenNops[$nop]) || SismiopData::where('nop', $nop)->exists()) {
                $this->report['duplikat_nop']++;
                continue;
            }

            $seenNops[$nop] = true;

            $payload = [
                'nop' => $nop,
                'objek_pajak_jalan_dusun_op' => $this->extractValue($row, 2),
                'objek_pajak_rt' => $this->extractValue($row, 3),
                'objek_pajak_rw' => $this->extractValue($row, 4),
                'objek_pajak_desa' => $this->extractValue($row, 5),
                'subjek_pajak_nama_wajib_pajak' => $this->extractValue($row, 6),
                'subjek_pajak_jalan_dusun' => $this->extractValue($row, 7),
                'subjek_pajak_rt' => $this->extractValue($row, 8),
                'subjek_pajak_rw' => $this->extractValue($row, 9),
                'subjek_pajak_desa_kel' => $this->extractValue($row, 10),
                'subjek_pajak_kabupaten_kota' => $this->extractValue($row, 11),
                'bumi' => $this->extractValue($row, 12, null),
                'bng' => $this->extractValue($row, 13, null),
                'jns_bumi' => $this->extractValue($row, 14, null),
                'usulan_pembetulan' => $this->extractValue($row, 15, null),
                'blok' => $this->extractValue($row, 16, null),
                'no_urut' => $this->extractValue($row, 17, null),
            ];

            if ($this->supportsNopRaw) {
                $payload['nop_raw'] = $nopRaw;
            }

            $this->data[] = $payload;
            $this->report['berhasil']++;
        }
    }

    protected function normalizeRow(Collection $row): array
    {
        $values = array_values($row->toArray());

        return array_map(function ($value) {
            if (is_string($value)) {
                return trim(preg_replace('/\s+/', ' ', $value));
            }

            return $value;
        }, $values);
    }

    protected function rowContainsFooterSection(array $normalizedRow): bool
    {
        foreach ($normalizedRow as $value) {
            if (is_string($value) && str_contains($value, 'KETERANGAN JENIS BUMI')) {
                return true;
            }
        }

        return false;
    }

    protected function extractValue(array $row, int $index, $default = '')
    {
        if (array_key_exists($index, $row)) {
            $cleaned = $this->cleanValue($row[$index]);

            if ($cleaned !== '') {
                return $cleaned;
            }
        }

        return $default;
    }

    protected function cleanValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return trim(preg_replace('/\s+/', ' ', $value));
        }

        return '';
    }
}
