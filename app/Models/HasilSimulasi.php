<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilSimulasi extends Model
{
    use HasFactory;

    protected $table = 'tb_hasil_simulasi';
    protected $primaryKey = 'id';
    
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'id_obat',
        'skenario',
        'statistik',
        'created_at'
    ];
    
    protected $casts = [
        'statistik' => 'array'
    ];
    
    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}