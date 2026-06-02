<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            if (! Schema::hasColumn('tanah', 'nop')) {
                $table->string('nop', 30)->nullable()->index()->after('no_urut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tanah', function (Blueprint $table) {
            if (Schema::hasColumn('tanah', 'nop')) {
                $table->dropIndex(['nop']);
                $table->dropColumn('nop');
            }
        });
    }
};