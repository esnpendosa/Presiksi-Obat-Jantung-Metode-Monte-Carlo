<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skenario extends Model
{
    use HasFactory;
    
    protected $table = 'tb_skenario';
    protected $primaryKey = 'id_skenario';
    
    protected $fillable = [
        'nama_skenario',
        'jumlah_simulasi'
    ];
}