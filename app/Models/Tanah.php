<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tanah extends Model
{
    protected $table = 'tanah';
    protected $fillable = [
        'no_urut',
        'nama_wajib_ipeda',
        'tempat_tinggal',
        'nomor_persil',
        'kelas_desa',
        'luas_ha',
        'luas_da',
        'ipeda_r',
        'ipeda_s',
        'sebab_perubahan',
        'tgl_perubahan',
        'jenis_tanah',
        'blok_id',
    ];

    protected $casts = [
        'luas_ha' => 'decimal:2',
        'luas_da' => 'decimal:2',
        'ipeda_r' => 'decimal:2',
        'ipeda_s' => 'decimal:2',
        'tgl_perubahan' => 'date',
        'blok_id' => 'integer',
    ];

    public function blok(): BelongsTo
    {
        return $this->belongsTo(MapBlok::class, 'blok_id');
    }
}
