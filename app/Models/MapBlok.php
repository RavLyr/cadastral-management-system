<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapBlok extends Model
{
    protected $table = 'map_blok';

    protected $fillable = [
        'nama_blok',
        'skala',
        'file_pdf',
        'deskripsi',
        // Add other fields if needed
    ];
}
