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
        Schema::create('map_blok', function (Blueprint $table) {
            $table->id();
            $table->string('nama_blok', 50)->unique();
            $table->string('file_pdf', 255);
            $table->string('skala', 50)->nullable();     // ex: "1:2000"
            $table->text('deskripsi')->nullable();
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
