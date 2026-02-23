@extends('layouts.app')

@section('title', 'Analisis Komparatif Metrik Evaluasi')

@section('content')
<div class="container py-3">

    {{-- HERO HEADER --}}
    <div class="mc-hero mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <div class="mc-hero-title">Analisis Komparatif Metrik Evaluasi</div>
                <div class="mc-hero-sub">
                    Perbandingan kinerja prediksi (MAPE, MAD, MSE) untuk setiap skenario pada setiap obat.
                </div>
            </div>
            <div class="mc-hero-actions d-flex gap-2">
                <a href="{{ url()->previous() }}" class="btn btn-light btn-sm mc-btn-soft">← Kembali</a>
                <a href="{{ route('hasil.index') }}" class="btn btn-dark btn-sm mc-btn-solid">Halaman Utama Hasil</a>
            </div>
        </div>
    </div>

    @php
        // helper badge warna berdasarkan MAPE
        $badgeClass = function($mape){
            if ($mape === null) return 'mc-badge-muted';
            if ($mape <= 10) return 'mc-badge-best';
            if ($mape <= 20) return 'mc-badge-good';
            if ($mape <= 50) return 'mc-badge-warn';
            return 'mc-badge-bad';
        };

        // cari best overall (MAPE terkecil dari semua obat & skenario)
        $bestOverall = null;
        foreach ($statistics as $st) {
            foreach (['s1','s2','s3'] as $k) {
                $row = $st[$k] ?? null;
                if (!$row) continue;
                if ($bestOverall === null || (float)$row->MAPE < (float)$bestOverall->MAPE) {
                    $bestOverall = (object)[
                        'obat' => $st['obat'] ?? '-',
                        'skenario' => $row->skenario ?? '-',
                        'MAPE' => (float)($row->MAPE ?? 0),
                    ];
                }
            }
        }

        $countObat = count($statistics ?? []);
        
        // Siapkan data untuk chart
        $chartData = [];
        foreach ($statistics as $stat) {
            $obat = $stat['obat'];
            $chartData[$obat] = [
                'MAPE' => [
                    'Skenario 1' => $stat['s1']->MAPE ?? 0,
                    'Skenario 2' => $stat['s2']->MAPE ?? 0,
                    'Skenario 3' => $stat['s3']->MAPE ?? 0,
                ],
                'MAD' => [
                    'Skenario 1' => $stat['s1']->MAD ?? 0,
                    'Skenario 2' => $stat['s2']->MAD ?? 0,
                    'Skenario 3' => $stat['s3']->MAD ?? 0,
                ],
                'MSE' => [
                    'Skenario 1' => $stat['s1']->MSE ?? 0,
                    'Skenario 2' => $stat['s2']->MSE ?? 0,
                    'Skenario 3' => $stat['s3']->MSE ?? 0,
                ]
            ];
        }
    @endphp

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="mc-card">
                <div class="mc-card-label">Jumlah Obat</div>
                <div class="mc-card-value">{{ $countObat }}</div>
                <div class="mc-card-hint">Baris yang dibandingkan di tabel.</div>
            </div>
        </div>
        <div class="col-12 col-md-8">
            <div class="mc-card">
                <div class="mc-card-label">Best Overall (MAPE terkecil)</div>
                @if($bestOverall)
                    <div class="mc-card-value">
                        {{ $bestOverall->obat }}
                        <span class="mc-pill {{ $badgeClass($bestOverall->MAPE) }}">
                            {{ $bestOverall->skenario }} • {{ number_format($bestOverall->MAPE, 2) }}%
                        </span>
                    </div>
                    <div class="mc-card-hint">Gunakan ini untuk rekomendasi default jika dibutuhkan.</div>
                @else
                    <div class="mc-card-value">-</div>
                    <div class="mc-card-hint">Belum ada data metrik evaluasi.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- GRAPHS SECTION --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="mc-card">
                <div class="mc-card-label mb-3">Visualisasi Komparatif Metrik</div>
                
                {{-- Tabs untuk memilih metrik --}}
                <ul class="nav nav-tabs mb-3" id="metricTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mape-tab" data-bs-toggle="tab" data-bs-target="#mape-chart" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>MAPE (%)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mad-tab" data-bs-toggle="tab" data-bs-target="#mad-chart" type="button" role="tab">
                            <i class="fas fa-chart-bar me-2"></i>MAD
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mse-tab" data-bs-toggle="tab" data-bs-target="#mse-chart" type="button" role="tab">
                            <i class="fas fa-chart-area me-2"></i>MSE
                        </button>
                    </li>
                </ul>

                {{-- Tab content --}}
                <div class="tab-content">
                    {{-- MAPE Chart --}}
                    <div class="tab-pane fade show active" id="mape-chart" role="tabpanel">
                        <div class="chart-container" style="position: relative; height: 400px;">
                            <canvas id="mapeChart"></canvas>
                        </div>
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-info-circle me-1"></i> MAPE (Mean Absolute Percentage Error) menunjukkan persentase rata-rata kesalahan prediksi. Semakin kecil semakin baik.
                        </div>
                    </div>

                    {{-- MAD Chart --}}
                    <div class="tab-pane fade" id="mad-chart" role="tabpanel">
                        <div class="chart-container" style="position: relative; height: 400px;">
                            <canvas id="madChart"></canvas>
                        </div>
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-info-circle me-1"></i> MAD (Mean Absolute Deviation) menunjukkan rata-rata penyimpangan absolut antara prediksi dan aktual.
                        </div>
                    </div>

                    {{-- MSE Chart --}}
                    <div class="tab-pane fade" id="mse-chart" role="tabpanel">
                        <div class="chart-container" style="position: relative; height: 400px;">
                            <canvas id="mseChart"></canvas>
                        </div>
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-info-circle me-1"></i> MSE (Mean Squared Error) memberikan bobot lebih pada kesalahan besar karena pengkuadratan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="mc-table-card">
        <div class="mc-table-top">
            <div class="mc-table-title">Tabel Komparatif</div>
            <div class="mc-table-note">Badge hijau = MAPE lebih kecil (lebih baik).</div>
        </div>

        <div class="table-responsive mc-table-wrap">
            <table class="table table-hover align-middle mb-0 mc-table">
                <thead>
                    <tr class="text-center">
                        <th rowspan="2" class="mc-th-left" style="min-width:260px;">Nama Obat</th>
                        <th colspan="3">Skenario 1</th>
                        <th colspan="3">Skenario 2</th>
                        <th colspan="3">Skenario 3</th>
                        <th rowspan="2" style="min-width:180px;">Terbaik</th>
                    </tr>
                    <tr class="text-center">
                        <th>MAD</th><th>MSE</th><th>MAPE</th>
                        <th>MAD</th><th>MSE</th><th>MAPE</th>
                        <th>MAD</th><th>MSE</th><th>MAPE</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($statistics as $stat)
                        @php
                            $s1 = $stat['s1'] ?? null;
                            $s2 = $stat['s2'] ?? null;
                            $s3 = $stat['s3'] ?? null;
                            $best = $stat['best'] ?? null;
                        @endphp

                        <tr>
                            <td class="mc-name">
                                <div class="mc-name-title">{{ $stat['obat'] ?? '-' }}</div>
                                <div class="mc-name-sub">Ringkasan metrik tiap skenario</div>
                            </td>

                            {{-- Skenario 1 --}}
                            <td class="text-center">{{ $s1 ? number_format($s1->MAD ?? 0, 2) : '-' }}</td>
                            <td class="text-center">{{ $s1 ? number_format($s1->MSE ?? 0, 2) : '-' }}</td>
                            <td class="text-center">
                                @if($s1)
                                    <span class="mc-pill {{ $badgeClass((float)($s1->MAPE ?? 0)) }}">
                                        {{ number_format($s1->MAPE ?? 0, 2) }}%
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Skenario 2 --}}
                            <td class="text-center">{{ $s2 ? number_format($s2->MAD ?? 0, 2) : '-' }}</td>
                            <td class="text-center">{{ $s2 ? number_format($s2->MSE ?? 0, 2) : '-' }}</td>
                            <td class="text-center">
                                @if($s2)
                                    <span class="mc-pill {{ $badgeClass((float)($s2->MAPE ?? 0)) }}">
                                        {{ number_format($s2->MAPE ?? 0, 2) }}%
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Skenario 3 --}}
                            <td class="text-center">{{ $s3 ? number_format($s3->MAD ?? 0, 2) : '-' }}</td>
                            <td class="text-center">{{ $s3 ? number_format($s3->MSE ?? 0, 2) : '-' }}</td>
                            <td class="text-center">
                                @if($s3)
                                    <span class="mc-pill {{ $badgeClass((float)($s3->MAPE ?? 0)) }}">
                                        {{ number_format($s3->MAPE ?? 0, 2) }}%
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Terbaik --}}
                            <td class="text-center">
                                @if ($best)
                                    <div class="mc-best">
                                        <div class="mc-best-title">{{ $best->skenario ?? '-' }}</div>
                                        <div class="mc-best-pill">
                                            <span class="mc-pill {{ $badgeClass((float)($best->MAPE ?? 0)) }}">
                                                {{ number_format($best->MAPE ?? 0, 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                Belum ada data analisis yang dapat ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Include Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartData = @json($chartData);
        const obatNames = Object.keys(chartData);
        const skenarioNames = ['Skenario 1', 'Skenario 2', 'Skenario 3'];
        
        // Warna untuk setiap skenario
        const skenarioColors = {
            'Skenario 1': '#3B82F6', // Biru
            'Skenario 2': '#10B981', // Hijau
            'Skenario 3': '#F59E0B', // Kuning
        };
        
        // Fungsi untuk membuat chart
        function createChart(canvasId, metricType, yAxisLabel) {
            const datasets = skenarioNames.map(skenario => {
                const data = obatNames.map(obat => chartData[obat][metricType][skenario] || 0);
                
                return {
                    label: skenario,
                    data: data,
                    backgroundColor: skenarioColors[skenario] + '20',
                    borderColor: skenarioColors[skenario],
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    pointBackgroundColor: skenarioColors[skenario],
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                };
            });
            
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: obatNames,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.y;
                                    const label = context.dataset.label;
                                    const suffix = metricType === 'MAPE' ? '%' : '';
                                    return `${label}: ${value.toFixed(2)}${suffix}`;
                                }
                            }
                        },
                        datalabels: {
                            display: false // Nonaktifkan label data pada line chart
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            },
                            title: {
                                display: true,
                                text: yAxisLabel,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    const suffix = metricType === 'MAPE' ? '%' : '';
                                    return value.toFixed(1) + suffix;
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'nearest'
                    }
                },
                plugins: [ChartDataLabels]
            });
        }
        
        // Inisialisasi semua chart
        const mapeChart = createChart('mapeChart', 'MAPE', 'MAPE (%)');
        const madChart = createChart('madChart', 'MAD', 'MAD');
        const mseChart = createChart('mseChart', 'MSE', 'MSE');
        
        // Tambahkan chart bar untuk perbandingan side-by-side (opsional)
        function createBarChart() {
            const ctx = document.createElement('canvas');
            ctx.id = 'barComparisonChart';
            document.querySelector('.chart-container').appendChild(ctx);
            
            // Dataset untuk chart bar
            const barData = [];
            obatNames.forEach(obat => {
                skenarioNames.forEach(skenario => {
                    barData.push({
                        obat: obat,
                        skenario: skenario,
                        MAPE: chartData[obat]?.MAPE[skenario] || 0,
                        MAD: chartData[obat]?.MAD[skenario] || 0,
                        MSE: chartData[obat]?.MSE[skenario] || 0
                    });
                });
            });
            
            // Ini bisa digunakan untuk visualisasi tambahan
            console.log('Data tersedia untuk visualisasi:', barData);
        }
        
        // Panggil fungsi untuk membuat chart bar (opsional)
        // createBarChart();
    });
</script>

{{-- STYLE (cukup di blade ini aja) --}}
<style>
    :root{
        --mc-bg: #0b1220;
        --mc-bg2:#101b33;
        --mc-card:#ffffff;
        --mc-text:#111827;
        --mc-muted:#6b7280;
        --mc-border:#e5e7eb;
        --mc-soft:#f3f4f6;
    }

    .mc-hero{
        border-radius: 16px;
        padding: 18px 18px;
        color: #fff;
        background: radial-gradient(1200px 400px at 10% 10%, rgba(99,102,241,.35), transparent 55%),
                    radial-gradient(900px 420px at 90% 0%, rgba(16,185,129,.25), transparent 55%),
                    linear-gradient(135deg, var(--mc-bg), var(--mc-bg2));
        border: 1px solid rgba(255,255,255,.10);
        box-shadow: 0 10px 30px rgba(0,0,0,.18);
    }
    .mc-hero-title{
        font-size: 20px;
        font-weight: 800;
        letter-spacing: .2px;
        margin-bottom: 4px;
    }
    .mc-hero-sub{
        font-size: 13px;
        opacity: .9;
    }
    .mc-btn-soft{
        background: rgba(255,255,255,.14) !important;
        border: 1px solid rgba(255,255,255,.18) !important;
        color: #fff !important;
    }
    .mc-btn-solid{
        background: rgba(255,255,255,.92) !important;
        border: 1px solid rgba(255,255,255,.18) !important;
        color: #0b1220 !important;
        font-weight: 700;
    }

    .mc-card{
        background: var(--mc-card);
        border: 1px solid var(--mc-border);
        border-radius: 14px;
        padding: 14px 14px;
        box-shadow: 0 8px 18px rgba(17,24,39,.06);
        height: 100%;
    }
    .mc-card-label{
        font-size: 12px;
        color: var(--mc-muted);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 6px;
    }
    .mc-card-value{
        font-size: 18px;
        font-weight: 800;
        color: var(--mc-text);
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .mc-card-hint{
        margin-top: 6px;
        font-size: 12px;
        color: var(--mc-muted);
    }

    .mc-table-card{
        background: #fff;
        border: 1px solid var(--mc-border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(17,24,39,.06);
    }
    .mc-table-top{
        padding: 12px 14px;
        border-bottom: 1px solid var(--mc-border);
        background: linear-gradient(180deg, #ffffff, #fafafa);
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 12px;
        flex-wrap: wrap;
    }
    .mc-table-title{
        font-weight: 900;
        color: var(--mc-text);
        letter-spacing: .2px;
    }
    .mc-table-note{
        font-size: 12px;
        color: var(--mc-muted);
    }

    .mc-table-wrap{
        max-height: 520px;
        overflow: auto;
    }
    .mc-table thead th{
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc !important;
        border-color: var(--mc-border) !important;
        font-size: 12px;
        font-weight: 900;
        color: #0f172a;
        white-space: nowrap;
    }
    .mc-th-left{
        text-align: left !important;
    }
    .mc-table tbody td{
        border-color: var(--mc-border) !important;
        font-size: 12px;
        white-space: nowrap;
    }
    .mc-table tbody tr:nth-child(even){
        background: #fcfcfd;
    }

    .mc-name .mc-name-title{
        font-weight: 900;
        color: #0f172a;
        line-height: 1.1;
    }
    .mc-name .mc-name-sub{
        font-size: 11px;
        color: var(--mc-muted);
        margin-top: 4px;
    }

    .mc-pill{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-weight: 900;
        font-size: 11px;
        border: 1px solid transparent;
        letter-spacing: .2px;
    }
    .mc-badge-best{ background: rgba(16,185,129,.14); color: #065f46; border-color: rgba(16,185,129,.35); }
    .mc-badge-good{ background: rgba(59,130,246,.14); color: #1e40af; border-color: rgba(59,130,246,.35); }
    .mc-badge-warn{ background: rgba(245,158,11,.16); color: #92400e; border-color: rgba(245,158,11,.35); }
    .mc-badge-bad { background: rgba(239,68,68,.14); color: #991b1b; border-color: rgba(239,68,68,.35); }
    .mc-badge-muted{ background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }

    .mc-best .mc-best-title{
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 6px;
        font-size: 12px;
    }
    
    /* Styling untuk tabs dan chart */
    .nav-tabs {
        border-bottom: 2px solid var(--mc-border);
    }
    
    .nav-tabs .nav-link {
        color: var(--mc-muted);
        font-weight: 600;
        border: none;
        padding: 10px 20px;
        margin-right: 5px;
        border-radius: 8px 8px 0 0;
    }
    
    .nav-tabs .nav-link:hover {
        color: var(--mc-text);
        background-color: var(--mc-soft);
    }
    
    .nav-tabs .nav-link.active {
        color: #3B82F6;
        background-color: rgba(59, 130, 246, 0.1);
        border-bottom: 3px solid #3B82F6;
    }
    
    .tab-content {
        padding-top: 20px;
    }
    
    .chart-container {
        background: white;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid var(--mc-border);
    }
</style>
@endsection