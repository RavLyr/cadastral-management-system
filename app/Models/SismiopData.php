<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SismiopData extends Model
{
    protected $table = 'dhr_desa_temurejo';

    protected $fillable = [
        'nop',
        'objek_pajak_jalan_dusun_op',
        'objek_pajak_rt',
        'objek_pajak_rw',
        'objek_pajak_desa',
        'subjek_pajak_nama_wajib_pajak',
        'subjek_pajak_jalan_dusun',
        'subjek_pajak_rt',
        'subjek_pajak_rw',
        'subjek_pajak_desa_kel',
        'subjek_pajak_kabupaten_kota',
        'bumi',
        'bng',
        'jns_bumi',
        'usulan_pembetulan',
        'blok',
        'no_urut',
        'map_blok_id',
    ];

    public function mapBlok()
    {
        return $this->belongsTo(MapBlok::class, 'map_blok_id');
    }
}
