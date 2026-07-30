<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tanah extends Model
{
    protected $table = 'tanah';
    protected $fillable = [
        'no_urut',
        'parent_id',
        'nop',
        'nop_raw',
        'nama_wajib_ipeda',
        'tempat_tinggal',
        'nomor_persil',
        'kelas_desa',
        'luas_ha',
        'luas_da',
        'luas_awal_ha',
        'luas_awal_da',
        'luas_sisa_ha',
        'luas_sisa_da',
        'ipeda_r',
        'ipeda_s',
        'sebab_perubahan',
        'tgl_perubahan',
        'jenis_tanah',
        'blok_id',
        'created_by',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'luas_ha' => 'decimal:2',
        'luas_da' => 'decimal:2',
        'luas_awal_ha' => 'decimal:4',
        'luas_awal_da' => 'decimal:4',
        'luas_sisa_ha' => 'decimal:4',
        'luas_sisa_da' => 'decimal:4',
        'ipeda_r' => 'decimal:2',
        'ipeda_s' => 'decimal:2',
        'tgl_perubahan' => 'date',
        'blok_id' => 'integer',
    ];

    public function blok(): BelongsTo
    {
        return $this->belongsTo(MapBlok::class, 'blok_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest('tgl_perubahan')->latest('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TanahHistory::class)->latest('tanggal_perubahan')->latest('id');
    }
}
