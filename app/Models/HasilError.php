<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilError extends Model
{
    use HasFactory;

    protected $table = 'tb_hasil_error';
    protected $primaryKey = 'id';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'id_obat',
        'skenario',
        'data_prediksi',
        'data_aktual',
        'AD',
        'SE',
        'APE',
        'simulasi',
        'hitung_error',
        'created_at'
    ];
    
    protected $casts = [
        'AD' => 'decimal:2',
        'SE' => 'decimal:2',
        'APE' => 'decimal:4',
        'hitung_error' => 'decimal:4'
    ];
    
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}