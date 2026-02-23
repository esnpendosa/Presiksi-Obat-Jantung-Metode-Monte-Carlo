<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodePermintaan extends Model
{
    use HasFactory;

    protected $table = 'tb_periode_permintaan';
    protected $primaryKey = 'id_periode';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'periode_tahun',
        'periode_bulan',
        'clopidogrel_75_mg',
        'candesartan_8_mg',
        'isosorbid_dinitrate_5_mg',
        'nitrokaf_retard_25_mg',
        'created_at'
    ];
}