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
    Schema::create('dhr_desa_temurejo', function (Blueprint $table) {
    $table->id();
    $table->string('nop')->nullable();
    $table->string('objek_pajak_jalan_dusun_op')->nullable();
    $table->string('objek_pajak_rt')->nullable();
    $table->string('objek_pajak_rw')->nullable();
    $table->string('objek_pajak_desa')->nullable();
    $table->string('subjek_pajak_nama_wajib_pajak')->nullable();
    $table->string('subjek_pajak_jalan_dusun')->nullable();
    $table->string('subjek_pajak_rt')->nullable();
    $table->string('subjek_pajak_rw')->nullable();
    $table->string('subjek_pajak_desa_kel')->nullable();
    $table->string('subjek_pajak_kabupaten_kota')->nullable();
    $table->string('bumi')->nullable();
    $table->string('bng')->nullable();
    $table->string('jns_bumi')->nullable();
    $table->string('usulan_pembetulan')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
