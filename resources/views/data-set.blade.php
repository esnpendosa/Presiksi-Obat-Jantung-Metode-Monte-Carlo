@extends('layouts.app')

@section('title', 'Data Set & Prediksi Obat Jantung')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            {{-- FILTER TAHUN --}}
            <form method="GET" action="{{ route('data.index') }}" class="row g-2 align-items-center mb-3">
                <div class="col-auto">
                    <label for="tahun" class="col-form-label">Filter Tahun</label>
                </div>
                <div class="col-auto">
                    <select name="tahun" id="tahun" class="form-select" onchange="this.form.submit()">
                        <option value="" {{ empty($selectedYear) ? 'selected' : '' }}>Semua</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ (int)$selectedYear === (int)$y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            {{-- KARTU DATA SET --}}
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-pills me-2"></i>Data Set Permintaan Obat Jantung
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('data.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-2"></i> Tambah Data
                        </a>
                        <a href="{{ route('data.import.form') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-file-excel me-2"></i> Import Excel
                        </a>

                        {{-- tombol hapus semua data set --}}
                        <form action="{{ route('data.clear') }}" method="POST"
                              onsubmit="return confirm('Hapus SEMUA data (harian, bulanan, simulasi)?');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash-alt me-2"></i> Reset
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">

                    {{-- pesan sukses & error --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- INFO ALUR SISTEM --}}
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Alur sistem:
                        <ol class="mb-0">
                            <li><strong>Import Dataset</strong> harian dari Excel (menu di atas).</li>
                            <li>Dataset otomatis terakumulasi menjadi <strong>data bulanan</strong>.</li>
                            <li>Buka menu <strong>Monte Carlo</strong> di sidebar untuk memilih skenario dan menjalankan simulasi.</li>
                            <li>Hasil simulasi dan error dapat dilihat di menu <strong>Hasil Error / Rekap</strong>.</li>
                        </ol>
                    </div>

                    {{-- TOMBOL LANJUT KE MONTE CARLO --}}
                    <div class="mb-3 text-end">
                        <a href="{{ route('monte-carlo.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-forward me-1"></i> Lanjut ke Simulasi Monte Carlo
                        </a>
                    </div>

                    {{-- RINGKASAN DATA SET HARIAN --}}
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-table me-2"></i>Ringkasan Data Set (Harian)
                            </h6>
                        </div>

                        <div class="card-body">
                            @php
                                $rowsHarian = $detailHarian ?? collect();
                            @endphp

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;" class="text-center">No</th>
                                            <th style="width: 140px;" class="text-center">Tanggal</th>
                                            @foreach($obats as $obat)
                                                <th class="text-center">
                                                    Permintaan Obat Jantung<br>
                                                    <small class="text-muted">{{ $obat->nama_obat }}</small>
                                                </th>
                                            @endforeach
                                            <th style="width: 170px;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse($rowsHarian as $index => $row)
                                            @php
                                                $tglKey = \Carbon\Carbon::parse($row->tanggal)->format('Y-m-d');
                                            @endphp

                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="text-center">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>

                                                @foreach($obats as $obat)
                                                    @php
                                                        $colName = $columnMapById[$obat->id_obat] ?? null;
                                                        $nilai   = $colName ? ($row->{$colName} ?? 0) : 0;
                                                    @endphp
                                                    <td class="text-center">{{ $nilai }}</td>
                                                @endforeach

                                                <td class="text-center">
                                                    <a href="{{ route('data.harian.edit', $tglKey) }}" class="btn btn-warning btn-sm">
                                                        Edit
                                                    </a>

                                                    <button type="button"
                                                            class="btn btn-danger btn-sm js-btn-delete"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalDeleteHarian"
                                                            data-tanggal="{{ $tglKey }}"
                                                            data-action="{{ route('data.harian.destroy', $tglKey) }}">
                                                        Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ 3 + $obats->count() }}" class="text-center">
                                                    Belum ada data permintaan harian. Silakan import Excel terlebih dahulu.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-3 text-center text-muted small mb-0">
                                Tabel Data SET (Harian) — sesuai data di Excel.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- MODAL KONFIRMASI HAPUS (Bootstrap) --}}
            <div class="modal fade" id="modalDeleteHarian" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Konfirmasi Hapus</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            Yakin hapus data tanggal <strong id="deleteTanggalText">-</strong>?
                            <div class="text-muted small mt-2">
                                Data bulanan untuk bulan terkait akan ikut ter-update.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>

                            <form id="formDeleteHarian" method="POST" action="#">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-btn-delete');
    if (!btn) return;

    const tanggal = btn.getAttribute('data-tanggal');
    const action  = btn.getAttribute('data-action');

    document.getElementById('deleteTanggalText').textContent = tanggal;
    document.getElementById('formDeleteHarian').setAttribute('action', action);
});
</script>

<style>
    .card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
        transition: box-shadow 0.3s ease;
    }
</style>
@endsection
