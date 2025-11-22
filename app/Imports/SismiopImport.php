<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SismiopImport implements ToCollection, WithHeadingRow
{
    /**
     * Flag to stop reading once the footer section is reached.
     */
    protected bool $skipRows = false;

    /**
     * Collected preview data.
     */
    public array $data = [];

    public function headingRow(): int
    {
        return 6; // sheet has multiple header rows, actual headings sit on row 6
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (!$row instanceof Collection) {
                $row = collect($row);
            }

            $normalizedRow = $this->normalizeRow($row);

            if ($this->rowContainsFooterSection($normalizedRow)) {
                $this->skipRows = true;
                continue;
            }

            if ($this->skipRows) {
                continue;
            }

            $nop = $this->extractValue($row, $normalizedRow, ['nop', 'nomor_objek_pajak'], 1);
            if ($nop === '') {
                continue;
            }

            $this->data[] = [
                'nop'                           => $nop,
                'objek_pajak_jalan_dusun_op'    => $this->extractValue($row, $normalizedRow, ['objek_pajak_jalan_dusun_op'], 2),
                'objek_pajak_rt'                => $this->extractValue($row, $normalizedRow, ['objek_pajak_rt'], 3),
                'objek_pajak_rw'                => $this->extractValue($row, $normalizedRow, ['objek_pajak_rw'], 4),
                'objek_pajak_desa'              => $this->extractValue($row, $normalizedRow, ['objek_pajak_desa'], 5),
                'subjek_pajak_nama_wajib_pajak' => $this->extractValue($row, $normalizedRow, ['subjek_pajak_nama_wajib_pajak'], 6),
                'subjek_pajak_jalan_dusun'      => $this->extractValue($row, $normalizedRow, ['subjek_pajak_jalan_dusun'], 7),
                'subjek_pajak_rt'               => $this->extractValue($row, $normalizedRow, ['subjek_pajak_rt'], 8),
                'subjek_pajak_rw'               => $this->extractValue($row, $normalizedRow, ['subjek_pajak_rw'], 9),
                'subjek_pajak_desa_kel'         => $this->extractValue($row, $normalizedRow, ['subjek_pajak_desa_kel'], 10),
                'subjek_pajak_kabupaten_kota'   => $this->extractValue($row, $normalizedRow, ['subjek_pajak_kabupaten_kota'], 11),
                'bumi'                          => $this->extractValue($row, $normalizedRow, ['bumi'], 12, null),
                'bng'                           => $this->extractValue($row, $normalizedRow, ['bng'], 13, null),
                'jns_bumi'                      => $this->extractValue($row, $normalizedRow, ['jns_bumi'], 14, null),
                'usulan_pembetulan'             => $this->extractValue($row, $normalizedRow, ['usulan_pembetulan'], 15, null),
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

    protected function extractValue(Collection $row, array $normalizedRow, array $possibleKeys, int $fallbackIndex, $default = '')
    {
        foreach ($possibleKeys as $key) {
            if ($row->has($key)) {
                $value = $row->get($key);

                if ($value === null) {
                    continue;
                }

                $cleaned = $this->cleanValue($value);
                if ($cleaned !== '') {
                    return $cleaned;
                }
            }
        }

        if (array_key_exists($fallbackIndex, $normalizedRow)) {
            $cleaned = $this->cleanValue($normalizedRow[$fallbackIndex]);

            if ($cleaned !== '') {
                return $cleaned;
            }
        }

        if ($default === null) {
            return null;
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
