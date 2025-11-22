<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sismiop extends Model
{
    protected $fillable = [
        'blok', 'persil', 'pemilik', 'luas', // sesuaikan dengan kolom Excel
    ];
}
