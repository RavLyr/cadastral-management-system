<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gis_bidang_tanah', function (Blueprint $table) {
            if (! Schema::hasColumn('gis_bidang_tanah', 'nop_raw')) {
                $table->string('nop_raw')->nullable()->after('nop');
            }
        });

        Schema::table('dhr_desa_temurejo', function (Blueprint $table) {
            if (! Schema::hasColumn('dhr_desa_temurejo', 'nop_raw')) {
                $table->string('nop_raw')->nullable()->after('nop');
            }
        });

        Schema::table('tanah', function (Blueprint $table) {
            if (! Schema::hasColumn('tanah', 'nop_raw')) {
                $table->string('nop_raw')->nullable()->after('nop');
            }
        });

        $this->backfillTable('gis_bidang_tanah', false);
        $this->backfillTable('dhr_desa_temurejo', false);
        $this->backfillTable('tanah', true);
    }

    public function down(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            if (Schema::hasColumn('tanah', 'nop_raw')) {
                $table->dropColumn('nop_raw');
            }
        });

        Schema::table('dhr_desa_temurejo', function (Blueprint $table) {
            if (Schema::hasColumn('dhr_desa_temurejo', 'nop_raw')) {
                $table->dropColumn('nop_raw');
            }
        });

        Schema::table('gis_bidang_tanah', function (Blueprint $table) {
            if (Schema::hasColumn('gis_bidang_tanah', 'nop_raw')) {
                $table->dropColumn('nop_raw');
            }
        });
    }

    private function backfillTable(string $table, bool $nopNullable): void
    {
        DB::table($table)
            ->select(['id', 'nop', 'nop_raw'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $nopNullable) {
                foreach ($rows as $row) {
                    $raw = $row->nop_raw ?: $row->nop;
                    $normalized = $this->normalizeNop($row->nop);

                    $payload = [
                        'nop_raw' => $raw,
                    ];

                    if ($normalized !== null) {
                        $payload['nop'] = $normalized;
                    } elseif ($nopNullable) {
                        $payload['nop'] = $row->nop;
                    }

                    DB::table($table)->where('id', $row->id)->update($payload);
                }
            });
    }

    private function normalizeNop(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);

        return $digits !== '' ? $digits : null;
    }
};
