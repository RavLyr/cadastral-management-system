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
        Schema::create('tanah', function (Blueprint $table) {
            $table->id();

            $table->string('no_urut', 20)->nullable();
            $table->string('nama_wajib_ipeda', 255);
            $table->string('tempat_tinggal', 255)->nullable();
            $table->string('nomor_persil', 50);
            $table->string('kelas_desa', 50)->nullable();

            $table->decimal('luas_ha', 10, 4)->nullable();
            $table->decimal('luas_da', 10, 4)->nullable();

            $table->decimal('ipeda_r', 15, 2)->nullable(); // rupiah
            $table->decimal('ipeda_s', 15, 2)->nullable(); // sen


            $table->text('sebab_perubahan')->nullable();
            $table->date('tgl_perubahan')->nullable();

            $table->enum('jenis_tanah', ['basah', 'kering']);
            $table->unique(['nomor_persil', 'blok_id']);


            // relasi blok
            $table->foreignId('blok_id')
                ->constrained('map_blok')
                ->onDelete('cascade');

            // tracking siapa yg input data (optional)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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
