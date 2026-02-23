<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonteCarlo extends Model
{
    use HasFactory;

    protected $table = 'tb_monte_carlo';
    protected $primaryKey = 'id';
    
    // Sesuaikan dengan struktur tabel SEBENARNYA
    protected $fillable = [
        'id_obat',
        'skenario',
        'nilai_frekuensi',
        'frekuensi',  // TAMBAHKAN ini jika kolom sudah ditambahkan
        'distribusi_probabilitas',
        'distribusi_kumulatif',
        'interval_bil_acak_awal',
        'interval_bil_acak_akhir',
        'simulasi_permintaan'
        // HAPUS 'pembangkitan_acak' jika tidak ada di tabel
    ];

    protected $casts = [
        'nilai_frekuensi' => 'decimal:2',
        'distribusi_probabilitas' => 'decimal:15',
        'distribusi_kumulatif' => 'decimal:15',
        'frekuensi' => 'integer',
        'simulasi_permintaan' => 'integer'
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}