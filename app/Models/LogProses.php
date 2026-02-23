<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogProses extends Model
{
    use HasFactory;

    protected $table = 'tb_log_proses';
    protected $primaryKey = 'id_log';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    
    protected $fillable = [
        'proses',
        'status',
        'pesan',
        'created_at'
    ];
}