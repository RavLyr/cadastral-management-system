<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gis_bidang_tanah', function (Blueprint $table) {
            $table->id();
            $table->string('nop')->index();
            $table->jsonb('properties')->nullable();
            $table->timestamps();
        });

        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE gis_bidang_tanah ADD COLUMN geom geometry(MultiPolygon, 4326)");
                DB::statement("CREATE INDEX gis_bidang_tanah_geom_idx ON gis_bidang_tanah USING GIST (geom)");
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS gis_bidang_tanah_geom_idx');
            }
        } catch (\Throwable $e) {
            report($e);
        }

        Schema::dropIfExists('gis_bidang_tanah');
    }
};
