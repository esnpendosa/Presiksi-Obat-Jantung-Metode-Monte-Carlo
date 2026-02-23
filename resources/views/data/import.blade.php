@extends('layouts.app')

@section('title', 'Import Data Excel')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-import"></i> Import Data dari Excel
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Petunjuk Import Data</h6>
                        <ul class="mb-0">
                            <li>File Excel berisi <strong>data harian permintaan obat jantung</strong>.</li>
                            <li>Format header yang didukung (sesuai template):
                                <ul>
                                    <li><strong>Tanggal</strong>: <code>Tanggal (dd/mm/yyyy)</code></li>
                                    <li><strong>Clopidogrel 75 Mg</strong>: <code>CLOPIDOGREL 75 MG TABL.</code></li>
                                    <li><strong>Nitrokaf Retard 2.5 Mg</strong>: <code>NITROKAF RETARD 2,5 Mg Tabl</code></li>
                                    <li><strong>Isosorbid Dinitrate 5 Mg</strong>: <code>ISOSORBID DINITRATE (ISDN) 5 MG TABL.</code></li>
                                    <li><strong>Candesartan 8 Mg</strong>: <code>CANDESARTAN 8 MG TABL.</code></li>
                                </ul>
                            </li>
                            <li>Setelah import:
                                <ol class="mb-0">
                                    <li>Data harian tersimpan di <code>tb_permintaan_obat_harian</code></li>
                                    <li>Data bulanan otomatis terakumulasi di <code>tb_periode_permintaan</code></li>
                                    <li>Kamu bisa lanjut ke menu <strong>Monte Carlo</strong> untuk simulasi.</li>
                                </ol>
                            </li>
                        </ul>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-times-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h6><i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan:</h6>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('data.import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                        @csrf

                        <div class="form-group">
                            <label for="excelFile" class="font-weight-bold">Pilih File Excel</label>
                            <div class="custom-file">
                                <input type="file" 
                                       class="custom-file-input @error('excelFile') is-invalid @enderror" 
                                       id="excelFile" 
                                       name="excelFile" 
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                <label class="custom-file-label" for="excelFile" id="fileLabel">
                                    Pilih file (.xlsx, .xls, .csv)
                                </label>
                                @error('excelFile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Maksimal ukuran file: 5MB. Format: Excel 2007+ (.xlsx), Excel 97-2003 (.xls), atau CSV (.csv)
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="overwriteData" name="overwriteData">
                                <label class="custom-control-label" for="overwriteData">
                                    Hapus semua data lama sebelum import
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Jika dicentang, seluruh data harian & bulanan yang ada akan dihapus dulu.
                            </small>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('data.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload"></i> Import Data
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-download"></i> Template Excel</h6>
                        </div>
                        <div class="card-body">
                            <p>Download template resmi agar format header dan urutan kolom sesuai sistem:</p>
                            <a href="{{ route('data.import.template') }}" 
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-file-excel"></i> Download Template
                            </a>
                            <small class="d-block text-muted mt-2">
                                Template berisi kolom <strong>Tanggal</strong> dan keempat obat jantung.
                            </small>
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
    $(document).ready(function() {
        $('#excelFile').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $('#fileLabel').text(fileName || 'Pilih file (.xlsx, .xls, .csv)');
        });

        $('#importForm').on('submit', function() {
            $('#submitBtn').prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        });

        $('#excelFile').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                if (fileSize > 5) {
                    alert('Ukuran file melebihi 5MB!');
                    $(this).val('');
                    $('#fileLabel').text('Pilih file (.xlsx, .xls, .csv)');
                }
            }
        });
    });
</script>
@endsection
