<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TanahImport implements ToCollection, WithStartRow
{
    /** @var array<int, array<string, mixed>> */
    protected array $rows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + $this->startRow();

            $mapped = [
                'row_number' => $rowNumber,
                'nama_wajib_ipeda' => $this->string($row[1] ?? null),
                'tempat_tinggal' => $this->string($row[2] ?? null),
                'blok' => $this->string($row[3] ?? null),
                'no_urut' => $this->string($row[4] ?? null),
                'nomor_persil' => $this->string($row[5] ?? null),
                'jenis_tanah' => $this->string($row[6] ?? null),
                'luas_ha' => $this->numeric($row[7] ?? null),
                'luas_da' => $this->numeric($row[8] ?? null),
                'ipeda_r' => $this->numeric($row[9] ?? null),
                'ipeda_s' => $this->numeric($row[10] ?? null),
                'sebab_perubahan' => $this->string($row[11] ?? null),
                'tgl_perubahan' => $this->dateValue($row[12] ?? null),
            ];

            $this->rows[] = $mapped;
        }
    }

    public function startRow(): int
    {
        return 7;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    private function string($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function numeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function dateValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
