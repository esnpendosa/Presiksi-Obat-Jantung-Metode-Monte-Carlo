@extends('layouts.app')

@section('title', 'Simulasi Monte Carlo')

@section('content')
<div class="mc-container">

    {{-- NOTIFIKASI --}}
    <div class="mc-top-bar">
        <div class="mc-top-left">
            @if(session('success'))
                <div class="mc-alert mc-alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mc-alert mc-alert-danger">
                    {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    {{-- BLOK ATAS: SKENARIO + OBAT --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Monte Carlo</span>
        </div>

        <form action="{{ route('monte-carlo.run') }}" method="POST">
            @csrf

            {{-- SKENARIO --}}
            <div class="mc-row mc-row-skenario">
                <span class="mc-label-inline">Skenario</span>
                @php
                    $skenDefault = old('skenario', $rekap['skenario'] ?? 'Skenario 1');
                @endphp
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 1"
                           {{ $skenDefault == 'Skenario 1' ? 'checked' : '' }}>
                    <span>1 (Harian) - Data 2024</span>
                </label>
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 2"
                           {{ $skenDefault == 'Skenario 2' ? 'checked' : '' }}>
                    <span>2 (Bulanan) - Data 2021-2022</span>
                </label>
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 3"
                           {{ $skenDefault == 'Skenario 3' ? 'checked' : '' }}>
                    <span>3 (Tahunan) - Data 2021-2024</span>
                </label>

                <span class="mc-label-inline ms-4">
                    Metode Monte Carlo 
                </span>

                <button type="submit" class="mc-btn mc-btn-primary mc-btn-hitung">
                    Hitung
                </button>
            </div>

            {{-- PILIH OBAT --}}
            <div class="mc-row mc-row-obat">
                <span class="mc-label-inline">Pilih Obat</span>
                @php
                    $selectedObatId = old('obat_id', $rekap['id_obat'] ?? ($obats[0]->id_obat ?? null));
                @endphp
                @foreach($obats as $obat)
                    <label class="mc-radio-inline">
                        <input type="radio" name="obat_id" value="{{ $obat->id_obat }}"
                               {{ (int)$selectedObatId === (int)$obat->id_obat ? 'checked' : '' }}>
                        <span>{{ $obat->nama_obat }}</span>
                    </label>
                @endforeach
            </div>

            {{-- INFO SKENARIO --}}
            <div class="mc-row mc-row-info">
                <div class="mc-info-box">
                    <strong>Skenario 1 (Harian):</strong> Data latih = Jan-Nov 2024, Data uji = Des 2024
                </div>
                <div class="mc-info-box">
                    <strong>Skenario 2 (Bulanan):</strong> Data latih = 2021 (12 bulan), Data uji = 2022 (12 bulan)
                </div>
                <div class="mc-info-box">
                    <strong>Skenario 3 (Tahunan):</strong> Data latih = 2021-2023, Data uji = 2024
                </div>
            </div>
        </form>
    </div>

    {{-- =======================
         TABEL DISTRIBUSI MONTE CARLO
       ======================= --}}
    @if(!empty($rekap))
    <div class="mc-block mc-block-table">
        <div class="mc-table-title">Tabel Monte Carlo (Distribusi Permintaan) - Data Training: {{ count($rekap['data_training']) }} periode</div>
        <div class="mc-table-wrapper">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jumlah Permintaan</th>
                        <th>Frekuensi</th>
                        <th>Probabilitas</th>
                        <th>Probabilitas Kumulatif</th>
                        <th>Interval Bilangan Acak</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rekap['distribusi'] as $idx => $row)
                    @php
                        $interval = collect($rekap['interval'] ?? [])
                                ->firstWhere('nilai', $row['nilai']);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $row['nilai'] }}</td>
                        <td>{{ $row['frekuensi'] }}</td>
                        <td>{{ number_format($row['probabilitas'], 4) }}</td>
                        <td>{{ number_format($row['kumulatif'], 4) }}</td>
                        <td>
                            @if($interval)
                                {{ $interval['interval_awal'] }} - {{ $interval['interval_akhir'] }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- =======================
         TABEL BILANGAN ACAK & SIMULASI
       ======================= --}}
    <div class="mc-block mc-block-table">
        <div class="mc-table-title">Tabel Bilangan Acak & Hasil Simulasi - Data Uji: {{ count($rekap['data_uji']) }} periode</div>
        <div class="mc-table-wrapper">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pembangkitan Bil.Acak </th>
                        <th>Permintaan Hasil Simulasi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rekap['simulasi'] as $row)
                    <tr>
                        <td>{{ $row['periode'] }}</td>
                        <td>{{ $row['bil_acak'] }}</td>
                        <td>{{ $row['permintaan'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- STATISTIK --}}
        <div class="mc-statistik">
            <div class="mc-stat-item">
                <span>Jumlah Simulasi:</span>
                <strong>{{ $rekap['jumlah_simulasi'] }}</strong>
            </div>
            <div class="mc-stat-item">
                <span>Rata-rata Permintaan:</span>
                <strong>{{ number_format($rekap['statistik']['rata_rata'], 2) }}</strong>
            </div>
            <div class="mc-stat-item">
                <span>Standar Deviasi:</span>
                <strong>{{ number_format($rekap['statistik']['standar_deviasi'], 2) }}</strong>
            </div>
        </div>

        {{-- ERROR METRICS --}}
        <div class="mc-error-metrics">
            <div class="mc-error-title">Metrik Evaluasi:</div>
            <div class="mc-error-item">
                <span>MAD:</span>
                <strong>{{ number_format($rekap['error_ringkas']['MAD'], 2) }}</strong>
            </div>
            <div class="mc-error-item">
                <span>MSE:</span>
                <strong>{{ number_format($rekap['error_ringkas']['RMSE'], 2) }}</strong>
            </div>
            <div class="mc-error-item">
                <span>MAPE:</span>
                <strong>{{ number_format($rekap['error_ringkas']['MAPE'], 2) }}%</strong>
            </div>
            <div
            <div class="mc-error-item">
                <span>Kriteria MAPE:</span>
                <strong>{{ $rekap['error_ringkas']['kriteria_mape'] }}</strong>
        </div>

        @php
            $hasilParams = [
                'obat_id' => $rekap['id_obat'],
                'skenario' => $rekap['skenario']
            ];
        @endphp

        <div class="mc-bottom-btn">
            <a href="{{ route('dashboard') }}" class="mc-btn mc-btn-outline">Kembali</a>
            <a href="{{ route('hasil.index', $hasilParams) }}" class="mc-btn mc-btn-primary ms-3">
                Lihat Detail Hasil Error (MAPE, MSE, MAD)
            </a>
        </div>
    </div>
    @endif

</div>
@endsection

{{-- STYLE --}}
<style>
    .mc-container {
        background: #f8fafc;
        color: #2d3748;
        padding: 16px 20px 30px 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .mc-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .mc-alert {
        padding: 6px 10px;
        font-size: 11px;
        border-radius: 6px;
        border-left-width: 3px;
        border-left-style: solid;
        background: #f1f5f9;
    }
    .mc-alert-success { border-left-color: #16a34a; color: #166534; }
    .mc-alert-danger  { border-left-color: #dc2626; color: #991b1b; }

    .mc-block {
        border: 1px solid #e2e8f0;
        padding: 8px 10px 10px 10px;
        margin-bottom: 12px;
        background: #ffffff;
        border-radius: 10px;
    }
    .mc-block-header span {
        font-size: 13px;
        font-weight: 600;
        color: #1a5fb4;
    }

    .mc-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .mc-row-skenario { gap: 8px; }
    .mc-row-obat     { margin-top: 6px; gap: 8px; }
    .mc-row-info     { margin-top: 8px; flex-direction: column; align-items: flex-start; }

    .mc-label-inline {
        font-size: 12px;
        margin-right: 8px;
        font-weight: 500;
        color: #2d3748;
    }
    .mc-radio-inline {
        font-size: 12px;
        margin-right: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .mc-radio-inline input[type="radio"] {
        margin: 0;
        width: 13px;
        height: 13px;
        accent-color: #0d6efd;
    }

    .mc-info-box {
        font-size: 11px;
        color: #4b5563;
        background: #f9fafb;
        padding: 4px 8px;
        border-radius: 4px;
        margin-bottom: 4px;
        width: 100%;
    }

    .mc-btn {
        border: 1px solid #0d6efd;
        background: white;
        color: #0d6efd;
        padding: 4px 16px;
        font-size: 12px;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .mc-btn-primary { background: #0d6efd; color: #fff; }
    .mc-btn-outline { background: transparent; }
    .mc-btn:hover {
        background: #0d6efd;
        color: #fff;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(13,110,253,0.3);
    }
    .mc-btn-hitung { margin-left: auto; }

    .mc-block-table { margin-top: 8px; }
    .mc-table-title {
        text-align: center;
        font-size: 12px;
        margin-bottom: 4px;
        font-weight: 600;
        color: #1a5fb4;
    }
    .mc-table-wrapper {
        border: 1px solid #e2e8f0;
        max-height: 280px;
        overflow-y: auto;
        background: #ffffff;
        border-radius: 8px;
    }
    .mc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        color: #2d3748;
    }
    .mc-table thead tr { background: #f1f5f9; }
    .mc-table th,
    .mc-table td {
        border: 1px solid #e2e8f0;
        padding: 4px 6px;
        text-align: center;
        white-space: nowrap;
    }
    .mc-table tbody tr:nth-child(even) { background: #f9fafb; }

    .mc-statistik {
        display: flex;
        justify-content: space-around;
        margin-top: 12px;
        padding: 8px;
        background: #f8fafc;
        border-radius: 6px;
    }
    .mc-stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 11px;
    }
    .mc-stat-item span {
        color: #718096;
        margin-bottom: 2px;
    }
    .mc-stat-item strong {
        color: #1a5fb4;
        font-size: 12px;
    }

    .mc-error-metrics {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 10px;
        padding: 10px;
        background: #fef3c7;
        border-radius: 6px;
        border: 1px solid #fde68a;
    }
    .mc-error-title {
        font-size: 11px;
        font-weight: 600;
        color: #92400e;
    }
    .mc-error-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 11px;
    }
    .mc-error-item span {
        color: #92400e;
        margin-bottom: 2px;
    }
    .mc-error-item strong {
        color: #dc2626;
        font-size: 12px;
    }

    .mc-bottom-btn {
        margin-top: 12px;
        display: flex;
        justify-content: flex-start;
        gap: 8px;
    }

    .content-wrapper,
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    @media (max-width: 768px) {
        .mc-container { padding: 12px; }
        .mc-row-skenario,
        .mc-row-obat {
            flex-direction: column;
            align-items: flex-start;
        }
        .mc-btn-hitung {
            margin-top: 6px;
            width: 100%;
        }
        .mc-statistik,
        .mc-error-metrics {
            flex-direction: column;
            gap: 6px;
        }
    }
</style>