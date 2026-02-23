<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetrikEvaluasi extends Model
{
    use HasFactory;

    protected $table = 'tb_metrik_evaluasi';
    protected $primaryKey = 'id';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'id_obat',
        'skenario',
        'MAPE',
        'MSE',
        'MAD',
        'created_at'
    ];
    
    protected $casts = [
        'MAPE' => 'decimal:4',
        'MSE' => 'decimal:4',
        'MAD' => 'decimal:4'
    ];
    
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}