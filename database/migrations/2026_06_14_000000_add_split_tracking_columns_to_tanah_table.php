<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            if (! Schema::hasColumn('tanah', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tanah')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tanah', 'luas_awal_ha')) {
                $table->decimal('luas_awal_ha', 15, 4)->nullable()->after('luas_da');
            }

            if (! Schema::hasColumn('tanah', 'luas_awal_da')) {
                $table->decimal('luas_awal_da', 15, 4)->nullable()->after('luas_awal_ha');
            }

            if (! Schema::hasColumn('tanah', 'luas_sisa_ha')) {
                $table->decimal('luas_sisa_ha', 15, 4)->nullable()->after('luas_awal_da');
            }

            if (! Schema::hasColumn('tanah', 'luas_sisa_da')) {
                $table->decimal('luas_sisa_da', 15, 4)->nullable()->after('luas_sisa_ha');
            }
        });

        DB::table('tanah')
            ->whereNull('luas_awal_ha')
            ->whereNotNull('luas_ha')
            ->update(['luas_awal_ha' => DB::raw('luas_ha')]);

        DB::table('tanah')
            ->whereNull('luas_awal_da')
            ->whereNotNull('luas_da')
            ->update(['luas_awal_da' => DB::raw('luas_da')]);

        DB::table('tanah')
            ->whereNull('luas_sisa_ha')
            ->whereNotNull('luas_ha')
            ->update(['luas_sisa_ha' => DB::raw('luas_ha')]);

        DB::table('tanah')
            ->whereNull('luas_sisa_da')
            ->whereNotNull('luas_da')
            ->update(['luas_sisa_da' => DB::raw('luas_da')]);

        Schema::table('tanah_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('tanah_histories', 'luas_awal_da')) {
                $table->decimal('luas_awal_da', 15, 4)->nullable()->after('luas_awal');
            }

            if (! Schema::hasColumn('tanah_histories', 'luas_berubah_da')) {
                $table->decimal('luas_berubah_da', 15, 4)->nullable()->after('luas_berubah');
            }

            if (! Schema::hasColumn('tanah_histories', 'luas_sisa_da')) {
                $table->decimal('luas_sisa_da', 15, 4)->nullable()->after('luas_sisa');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tanah_histories', function (Blueprint $table) {
            foreach (['luas_sisa_da', 'luas_berubah_da', 'luas_awal_da'] as $column) {
                if (Schema::hasColumn('tanah_histories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tanah', function (Blueprint $table) {
            foreach (['luas_sisa_da', 'luas_sisa_ha', 'luas_awal_da', 'luas_awal_ha'] as $column) {
                if (Schema::hasColumn('tanah', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('tanah', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
            }
        });
    }
};
