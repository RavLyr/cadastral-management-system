<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TanahHistory extends Model
{
    protected $fillable = [
        'tanah_id',
        'jenis_perubahan',
        'tanggal_perubahan',
        'luas_awal',
        'luas_awal_da',
        'luas_berubah',
        'luas_berubah_da',
        'luas_sisa',
        'luas_sisa_da',
        'pemilik_lama',
        'pemilik_baru',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_perubahan' => 'date',
        'luas_awal' => 'decimal:4',
        'luas_awal_da' => 'decimal:4',
        'luas_berubah' => 'decimal:4',
        'luas_berubah_da' => 'decimal:4',
        'luas_sisa' => 'decimal:4',
        'luas_sisa_da' => 'decimal:4',
        'created_by' => 'integer',
    ];

    public function tanah(): BelongsTo
    {
        return $this->belongsTo(Tanah::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
