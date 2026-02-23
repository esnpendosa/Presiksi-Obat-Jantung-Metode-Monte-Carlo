@extends('layouts.app')

@section('title', 'Tentang Kami')

@section('content')
<div class="mc-container">

    {{-- BARIS ATAS: TITLE --}}
    <div class="mc-top-bar">
        <div class="mc-top-left">
            <h5 class="mb-0" style="font-size:14px;font-weight:700;">
                TENTANG KAMI
            </h5>
        </div>
    </div>

    {{-- BLOK PROFIL APLIKASI --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Profil Aplikasi</span>
        </div>

        <div class="mt-2" style="font-size:12px;line-height:1.6;">
            <p>
                <strong>{{ $appInfo['nama_aplikasi'] ?? 'Sistem Prediksi Permintaan Obat Jantung' }}</strong>
                merupakan aplikasi berbasis web yang digunakan untuk
                memprediksi kebutuhan obat jantung pada
                <strong>{{ $appInfo['rumah_sakit'] ?? 'RSUD Ibnu Sina' }}</strong>.
            </p>
            <p>
                Aplikasi ini menggunakan metode
                <strong>{{ $appInfo['metode'] ?? 'Monte Carlo Frekuensi–Range' }}</strong>
                untuk membangkitkan permintaan obat secara acak berdasarkan
                distribusi historis. Hasil prediksi kemudian dievaluasi dengan
                metrik <strong>MAD</strong>, <strong>MSE</strong>, dan <strong>MAPE</strong>.
            </p>
            <p class="mb-0">
                Versi Aplikasi: <strong>{{ $appInfo['versi'] ?? '1.0.0' }}</strong>
            </p>
        </div>
    </div>

    {{-- BLOK TUJUAN DAN MANFAAT --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Tujuan &amp; Manfaat</span>
        </div>

        <ul style="font-size:12px;margin-top:6px;padding-left:18px;">
            <li>Membantu depo farmasi memprediksi kebutuhan obat jantung setiap bulan.</li>
            <li>Mengurangi risiko kekurangan stok (stockout) maupun kelebihan stok.</li>
            <li>Menyediakan dashboard sederhana untuk mengelola dataset harian dan bulanan.</li>
            <li>Memberikan evaluasi akurasi prediksi menggunakan MAD, MSE, dan MAPE.</li>
        </ul>
    </div>

    {{-- BLOK FITUR UTAMA --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Fitur Utama</span>
        </div>

        <div class="mt-2" style="font-size:12px;">
            <ol style="padding-left:18px;">
                <li>
                    <strong>Data Set</strong>  
                    Mengelola data permintaan obat jantung:
                    <ul style="padding-left:16px;margin-top:2px;">
                        <li>Input manual data harian.</li>
                        <li>Import data harian dari file Excel.</li>
                        <li>Akumulasi otomatis menjadi data bulanan.</li>
                    </ul>
                </li>
                <li class="mt-1">
                    <strong>Simulasi Monte Carlo</strong>  
                    Memilih obat, skenario, jumlah simulasi, dan periode prediksi,
                    kemudian menjalankan simulasi Monte Carlo frekuensi–range.
                </li>
                <li class="mt-1">
                    <strong>Hasil Error / Rekap</strong>  
                    Menampilkan ringkasan metrik MAD, MSE, dan MAPE
                    serta tabel error per periode untuk setiap kombinasi obat dan skenario.
                </li>
                <li class="mt-1">
                    <strong>Analisis & Laporan</strong> (opsional)  
                    Menyediakan analisis komparatif antar skenario dan laporan
                    rekapitulasi metrik evaluasi.
                </li>
                <li class="mt-1">
                    <strong>Reset Data</strong>  
                    Menghapus seluruh dataset, hasil simulasi, dan log proses
                    untuk mengulang percobaan dari awal.
                </li>
            </ol>
        </div>
    </div>

    {{-- BLOK ALUR PENGGUNAAN SINGKAT --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Alur Penggunaan Singkat</span>
        </div>

        <div style="font-size:12px;margin-top:6px;">
            <ol style="padding-left:18px;">
                <li>
                    Buka menu <strong>Data Set</strong> untuk:
                    <ul style="padding-left:16px;margin-top:2px;">
                        <li>Import data harian dari Excel, atau</li>
                        <li>Input manual data harian per tanggal.</li>
                    </ul>
                </li>
                <li>
                    Sistem otomatis mengakumulasi data harian menjadi
                    <strong>data bulanan</strong> di tabel <code>tb_periode_permintaan</code>.
                </li>
                <li>
                    Buka menu <strong>Monte Carlo</strong>, pilih obat dan skenario,
                    lalu tentukan:
                    <ul style="padding-left:16px;margin-top:2px;">
                        <li><em>Jumlah simulasi</em> (misal: 1000)</li>
                        <li><em>Periode prediksi</em> (misal: 1 bulan ke depan)</li>
                    </ul>
                    lalu klik <strong>Hitung</strong>.
                </li>
                <li>
                    Hasil distribusi, interval bilangan acak, dan prediksi akan tampil
                    di tabel Monte Carlo. Sekaligus tersimpan ke database.
                </li>
                <li>
                    Buka menu <strong>Hasil Error</strong> untuk melihat nilai
                    MAD, MSE, MAPE, dan kategori akurasi per obat &amp; skenario.
                </li>
            </ol>
        </div>
    </div>

    {{-- TOMBOL KEMBALI --}}
    <div class="mc-bottom-btn">
        <a href="{{ route('dashboard') }}" class="mc-btn mc-btn-outline">
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection

{{-- STYLE SEDERHANA (pakai mc-* sama seperti halaman Monte Carlo) --}}
<style>
    .mc-container {
        background: var(--light-bg, #f8fafc);
        color: var(--dark-text, #2d3748);
        padding: 16px 20px 30px 20px;
        border-radius: 12px;
        border: 1px solid var(--border-color, #e2e8f0);
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .mc-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .mc-block {
        border: 1px solid var(--border-color, #e2e8f0);
        padding: 8px 10px 10px 10px;
        margin-bottom: 12px;
        background: #ffffff;
        border-radius: 10px;
    }
    .mc-block-header span {
        font-size: 13px;
        font-weight: 600;
        color: var(--primary-color, #1a5fb4);
    }
    .mc-btn {
        border: 1px solid var(--secondary-color, #0d6efd);
        background: white;
        color: var(--secondary-color, #0d6efd);
        padding: 4px 16px;
        font-size: 12px;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .mc-btn-outline {
        background: transparent;
    }
    .mc-btn:hover {
        background: var(--secondary-color, #0d6efd);
        color: #fff;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(13,110,253,0.3);
    }
    .mc-bottom-btn {
        margin-top: 8px;
        display: flex;
        justify-content: flex-start;
        gap: 8px;
    }
</style>
