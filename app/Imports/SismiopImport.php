<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\SismiopData; // Import model

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

    /**
     * Specify the starting row for the import.
     */
    public function startRow(): int
    {
        return 7; // Mulai dari baris ke-7
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $row = $this->normalizeRow($row);

            if ($this->rowContainsFooterSection($row)) {
                $this->skipRows = true;
                continue;
            }

            if ($this->skipRows) {
                continue;
            }

            $nop = $this->extractValue($row, 1);
            if ($nop === '' || SismiopData::where('nop', $nop)->exists()) {
                continue; // Skip jika NOP kosong atau sudah ada di database
            }

            $this->data[] = [
                'nop'                           => $nop,
                'objek_pajak_jalan_dusun_op'    => $this->extractValue($row, 2),
                'objek_pajak_rt'                => $this->extractValue($row, 3),
                'objek_pajak_rw'                => $this->extractValue($row, 4),
                'objek_pajak_desa'              => $this->extractValue($row, 5),
                'subjek_pajak_nama_wajib_pajak' => $this->extractValue($row, 6),
                'subjek_pajak_jalan_dusun'      => $this->extractValue($row, 7),
                'subjek_pajak_rt'               => $this->extractValue($row, 8),
                'subjek_pajak_rw'               => $this->extractValue($row, 9),
                'subjek_pajak_desa_kel'         => $this->extractValue($row, 10),
                'subjek_pajak_kabupaten_kota'   => $this->extractValue($row, 11),
                'bumi'                          => $this->extractValue($row, 12, null),
                'bng'                           => $this->extractValue($row, 13, null),
                'jns_bumi'                      => $this->extractValue($row, 14, null),
                'usulan_pembetulan'             => $this->extractValue($row, 15, null),
                'blok'                          => $this->extractValue($row, 16, null),
                'no_urut'                       => $this->extractValue($row, 17, null),
            ];
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
