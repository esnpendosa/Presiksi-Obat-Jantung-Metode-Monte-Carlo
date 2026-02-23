<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'tb_obat';
    protected $primaryKey = 'id_obat';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at'; // Tabel ini punya updated_at
    
    protected $fillable = [
        'nama_obat',
        'jenis_obat',
        'satuan',
        'created_at',
        'updated_at'
    ];
    
    // Relasi
    public function monteCarlo()
    {
        return $this->hasMany(MonteCarlo::class, 'id_obat', 'id_obat');
    }
    
    public function hasilError()
    {
        return $this->hasMany(HasilError::class, 'id_obat', 'id_obat');
    }
    
    public function metrikEvaluasi()
    {
        return $this->hasMany(MetrikEvaluasi::class, 'id_obat', 'id_obat');
    }
}