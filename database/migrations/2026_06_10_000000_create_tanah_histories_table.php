<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanah_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tanah_id')
                ->nullable()
                ->constrained('tanah')
                ->cascadeOnDelete();
            $table->string('jenis_perubahan');
            $table->date('tanggal_perubahan')->nullable();
            $table->decimal('luas_awal', 15, 4)->nullable();
            $table->decimal('luas_berubah', 15, 4)->nullable();
            $table->decimal('luas_sisa', 15, 4)->nullable();
            $table->string('pemilik_lama')->nullable();
            $table->string('pemilik_baru')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['tanah_id', 'tanggal_perubahan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanah_histories');
    }
};
