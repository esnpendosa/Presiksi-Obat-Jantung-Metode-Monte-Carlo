<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TentangKamiController extends Controller
{
    /**
     * Halaman statis "Tentang Kami".
     * Menjelaskan profil aplikasi, tujuan, dan alur singkat.
     */
    public function index()
    {
        // Informasi dasar aplikasi (bisa kamu ubah sesuai kebutuhan)
        $appInfo = [
            'nama_aplikasi' => 'Sistem Prediksi Permintaan Obat Jantung',
            'versi'         => '1.0.0',
            'rumah_sakit'   => 'RSUD Ibnu Sina',
            'metode'        => 'Monte Carlo',
        ];

        return view('tentang-kami', compact('appInfo'));
    }
}
