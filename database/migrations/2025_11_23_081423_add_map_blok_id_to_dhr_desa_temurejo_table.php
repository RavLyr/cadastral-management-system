<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dhr_desa_temurejo', function (Blueprint $table) {
            $table->foreignId('map_blok_id')
                  ->nullable()
                  ->after('no_urut')
                  ->constrained('map_blok')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dhr_desa_temurejo', function (Blueprint $table) {
            if (Schema::hasColumn('dhr_desa_temurejo', 'map_blok_id')) {
                $table->dropConstrainedForeignId('map_blok_id');
            }
        });
    }
};
