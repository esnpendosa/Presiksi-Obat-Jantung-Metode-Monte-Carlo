@extends('layouts.app')

@section('title', 'Hasil Error Monte Carlo')

@section('content')
<div class="mc-container">

    {{-- NOTIF ATAS --}}
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

    {{-- FILTER OBAT & SKENARIO --}}
    <div class="mc-block">
        <div class="mc-block-header">
            <span>Hasil Error Monte Carlo (MAPE, MSE, MAD)</span>
        </div>

        @php
        $selectedObatId = $selectedObatId ?? request('obat_id', $obats->first()->id_obat ?? null);
        $selectedSkenario = $selectedSkenario ?? request('skenario', 'Skenario 1');
        @endphp

        <form action="{{ route('hasil.index') }}" method="GET">
            <div class="mc-row mc-row-skenario">
                <span class="mc-label-inline">Obat</span>
                <select name="obat_id" class="mc-select">
                    @foreach($obats as $obat)
                    <option value="{{ $obat->id_obat }}"
                        {{ (int)$selectedObatId === (int)$obat->id_obat ? 'selected' : '' }}>
                        {{ $obat->nama_obat }}
                    </option>
                    @endforeach
                </select>

                <span class="mc-label-inline ms-3">Skenario</span>
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 1"
                        {{ $selectedSkenario == 'Skenario 1' ? 'checked' : '' }}>
                    <span>1 (Harian)</span>
                </label>
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 2"
                        {{ $selectedSkenario == 'Skenario 2' ? 'checked' : '' }}>
                    <span>2 (Bulanan)</span>
                </label>
                <label class="mc-radio-inline">
                    <input type="radio" name="skenario" value="Skenario 3"
                        {{ $selectedSkenario == 'Skenario 3' ? 'checked' : '' }}>
                    <span>3 (Tahunan)</span>
                </label>

                <button type="submit" class="mc-btn mc-btn-primary mc-btn-hitung">
                    Tampilkan
                </button>
            </div>
        </form>

        {{-- RINGKASAN METRIK (PANEL ATAS) --}}
        @if($ringkas)
        <div class="mc-row mc-row-param mt-3">
            <div class="mc-param">
                <span>Skenario</span>
                <strong>{{ $metrik->skenario ?? $selectedSkenario }}</strong>
            </div>
            <div class="mc-param">
                <span>MAD</span>
                <strong>{{ number_format($ringkas['mad'], 2) }}</strong>
            </div>
            <!-- <div class="mc-param">
                <span>MSE</span>
                <strong>{{ number_format($ringkas['mse'], 2) }}</strong>
            </div> -->
            <div class="mc-param">
                <span>MSE</span>
                <strong>{{ number_format($ringkas['rmse'], 2) }}</strong>
            </div>
            <div class="mc-param">
                <span>MAPE</span>
                <strong>{{ number_format($ringkas['mape'], 2) }}%</strong>
            </div>
            <div class="mc-param">
                <span>Kriteria MAPE</span>
                @php
                $kategori = $ringkas['kriteria'] ?? '-';
                $kategoriClass = '';
                if($kategori == 'Sangat Baik') $kategoriClass = 'mc-kategori-sangat-baik';
                elseif($kategori == 'Baik') $kategoriClass = 'mc-kategori-baik';
                elseif($kategori == 'Cukup') $kategoriClass = 'mc-kategori-cukup';
                else $kategoriClass = 'mc-kategori-buruk';
                @endphp
                <strong class="{{ $kategoriClass }}">{{ $kategori }}</strong>
            </div>
        </div>
        @endif
    </div>

    {{-- TABEL HASIL ERROR PER PERIODE --}}
    <div class="mc-block mc-block-table">
        <div class="mc-table-title">Tabel Hasil Error per Periode (Data Uji)</div>
        <div class="mc-table-wrapper">
            <table class="mc-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Periode</th>
                        <th>Data Prediksi (Simulasi)</th>
                        <th>Data Aktual (Data Uji)</th>
                        <th>AD</th>
                        <th>SE</th>
                        <th>APE (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($errorRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            {{-- INI KUNCI: STOP NGARANG TANGGAL DI BLADE --}}
                            <td>{{ $row->tanggal_tampil ?? '-' }}</td>

                            {{-- sesuaikan nama kolom, kalau DB kamu sudah underscore --}}
                            <td>{{ $row->data_prediksi ?? 0 }}</td>
                            <td>{{ $row->data_aktual ?? 0 }}</td>

                            <td>{{ number_format($row->AD ?? 0, 2) }}</td>
                            <td>{{ number_format($row->SE ?? 0, 2) }}</td>
                            <td>{{ number_format($row->APE ?? 0, 2) }}</td>
                        </tr>
                    @empty
                            <tr>
                                <td colspan="7" class="mc-table-empty">
                                    Belum ada data hasil error untuk kombinasi obat & skenario ini.
                                    <br><small>Jalankan simulasi Monte Carlo terlebih dahulu.</small>
                                </td>
                            </tr>
                            @endforelse
                </tbody>
            </table>
        </div>

        {{-- INFO JUMLAH DATA --}}
        @if($errorRows->count() > 0)
        <div class="mc-info-count">
            Total Data: <strong>{{ $errorRows->count() }}</strong> periode uji
            @if($ringkas)
            | Rata-rata MAPE: <strong>{{ number_format($ringkas['mape'], 2) }}%</strong>
            | Akurasi: <strong>{{ $ringkas['kriteria'] }}</strong>
            @endif
        </div>
        @endif

        {{-- SEKSI BARU: RINGKASAN METRIK DI BAWAH TABEL --}}
        @if($ringkas)

        @endif

        <div class="mc-bottom-btn">
            <a href="{{ route('monte-carlo.index', [
                'obat_id'  => request('obat_id'),
                'skenario' => request('skenario'),
            ]) }}" class="mc-btn mc-btn-outline">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Monte Carlo
            </a>

            @if($errorRows->count() > 0)
            <a href="{{ route('hasil.analisis', [
                'obat_id'  => request('obat_id'),
                'skenario' => request('skenario'),
            ]) }}" class="mc-btn mc-btn-primary">
                <i class="fas fa-chart-bar me-1"></i> Analisis Komparatif
            </a>
            @endif
        </div>
    </div>
</div>
@endsection

{{-- STYLE --}}
<style>
    /* === STYLE kamu yang lama tetap dipakai === */
    .mc-container {
        background: #f8fafc;
        color: #2d3748;
        padding: 16px 20px 30px 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
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

    .mc-alert-success {
        border-left-color: #16a34a;
        color: #166534;
        background: #dcfce7;
    }

    .mc-alert-danger {
        border-left-color: #dc2626;
        color: #991b1b;
        background: #fee2e2;
    }

    .mc-block {
        border: 1px solid #e2e8f0;
        padding: 12px 14px;
        margin-bottom: 16px;
        background: #ffffff;
        border-radius: 10px;
    }

    .mc-block-header span {
        font-size: 14px;
        font-weight: 600;
        color: #1a5fb4;
    }

    .mc-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        margin-top: 6px;
    }

    .mc-row-skenario {
        gap: 10px;
        align-items: center;
    }

    .mc-row-param {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .mc-label-inline {
        font-size: 13px;
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

    .mc-select {
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        padding: 6px 8px;
        font-size: 12px;
        min-width: 200px;
        background: white;
    }

    .mc-select:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
    }

    .mc-param {
        display: flex;
        flex-direction: column;
        font-size: 12px;
    }

    .mc-param span {
        margin-bottom: 4px;
        color: #718096;
        font-size: 11px;
    }

    .mc-param strong {
        font-size: 13px;
        color: #2d3748;
        font-weight: 600;
    }

    .mc-kategori-sangat-baik {
        color: #059669;
    }

    .mc-kategori-baik {
        color: #2563eb;
    }

    .mc-kategori-cukup {
        color: #d97706;
    }

    .mc-kategori-buruk {
        color: #dc2626;
    }

    .mc-btn {
        border: 1px solid #0d6efd;
        background: white;
        color: #0d6efd;
        padding: 6px 16px;
        font-size: 12px;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .mc-btn-primary {
        background: #0d6efd;
        color: #fff;
    }

    .mc-btn-outline {
        background: transparent;
    }

    .mc-btn:hover {
        background: #0d6efd;
        color: #fff;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
        transform: translateY(-1px);
    }

    .mc-btn-hitung {
        margin-left: auto;
    }

    .mc-block-table {
        margin-top: 8px;
    }

    .mc-table-title {
        text-align: center;
        font-size: 13px;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1a5fb4;
    }

    .mc-table-wrapper {
        border: 1px solid #e2e8f0;
        max-height: 320px;
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

    .mc-table thead tr {
        background: #f1f5f9;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .mc-table th,
    .mc-table td {
        border: 1px solid #e2e8f0;
        padding: 6px 8px;
        text-align: center;
        white-space: nowrap;
    }

    .mc-table th {
        font-weight: 600;
        color: #4b5563;
    }

    .mc-table tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    .mc-table tbody tr:hover {
        background: #f0f9ff;
    }

    .mc-table-empty {
        text-align: center;
        padding: 40px 0;
        color: #718096;
        font-size: 12px;
    }

    .mc-info-count {
        font-size: 11px;
        color: #4b5563;
        text-align: center;
        margin-top: 10px;
        padding: 8px;
        background: #f8fafc;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }

    .mc-info-count strong {
        color: #1a5fb4;
    }

    .mc-bottom-btn {
        margin-top: 16px;
        display: flex;
        justify-content: flex-start;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid #e2e8f0;
    }

    @media (max-width: 768px) {
        .mc-container {
            padding: 12px;
        }

        .mc-row-skenario {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .mc-select {
            min-width: 100%;
        }

        .mc-btn-hitung {
            margin-left: 0;
            width: 100%;
            margin-top: 6px;
        }

        .mc-row-param {
            flex-direction: column;
            gap: 12px;
        }

        .mc-bottom-btn {
            flex-direction: column;
        }

        .mc-bottom-btn .mc-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>