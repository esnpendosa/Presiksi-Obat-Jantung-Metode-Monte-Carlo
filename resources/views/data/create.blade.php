@extends('layouts.app')

@section('content')
<style>
    /* Hilangkan tombol panah pada input angka (Chrome, Edge, Safari) */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    /* Hilangkan tombol panah pada Firefox */
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Tambah Data Permintaan Obat Harian
                <span class="text-muted fw-normal">(format seperti template Excel)</span>
            </h3>
        </div>

        {{-- Pesan Sukses --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Pesan Error --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card-body">
            <form action="{{ route('data.store') }}" method="POST">
                @csrf

                {{-- TANGGAL (1 kolom, sama seperti kolom A di Excel) --}}
                <div class="mb-3">
                    <label for="tanggal_permintaan" class="form-label">
                        Tanggal Permintaan
                        <small class="text-muted">(setiap baris = data satu hari)</small>
                    </label>
                    <input
                        type="date"
                        class="form-control @error('tanggal_permintaan') is-invalid @enderror"
                        id="tanggal_permintaan"
                        name="tanggal_permintaan"
                        value="{{ old('tanggal_permintaan') }}"
                        required
                    >
                    @error('tanggal_permintaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Sistem akan menyimpan 1 baris data harian ke tabel
                        <strong>tb_permintaan_obat_harian</strong> dan otomatis
                        mengakumulasi ke tabel <strong>tb_periode_permintaan</strong>.
                    </small>
                </div>

                <hr>

                {{-- 4 KOLOM OBAT – MIRIP TEMPLATE EXCEL --}}
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            CLOPIDOGREL 75 MG TABL.
                        </label>
                        <input
                            type="number"
                            name="clopidogrel_75_mg"
                            class="form-control @error('clopidogrel_75_mg') is-invalid @enderror"
                            value="{{ old('clopidogrel_75_mg', 0) }}"
                            min="0"
                            step="1"
                        >
                        @error('clopidogrel_75_mg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Satuan: tablet per hari</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            NITROKAF RETARD 2,5 MG TABL.
                        </label>
                        <input
                            type="number"
                            name="nitrokaf_retard_25_mg"
                            class="form-control @error('nitrokaf_retard_25_mg') is-invalid @enderror"
                            value="{{ old('nitrokaf_retard_25_mg', 0) }}"
                            min="0"
                            step="1"
                        >
                        @error('nitrokart_retard_25_mg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Satuan: tablet per hari</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            ISOSORBID DINITRATE (ISDN) 5 MG TABL.
                        </label>
                        <input
                            type="number"
                            name="isosorbid_dinitrate_5_mg"
                            class="form-control @error('isosorbid_dinitrate_5_mg') is-invalid @enderror"
                            value="{{ old('isosorbid_dinitrate_5_mg', 0) }}"
                            min="0"
                            step="1"
                        >
                        @error('isosorbid_dinitrate_5_mg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Satuan: tablet per hari</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">
                            CANDESARTAN 8 MG TABL.
                        </label>
                        <input
                            type="number"
                            name="candesartan_8_mg"
                            class="form-control @error('candesartan_8_mg') is-invalid @enderror"
                            value="{{ old('candesartan_8_mg', 0) }}"
                            min="0"
                            step="1"
                        >
                        @error('condesartan_8_mg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Satuan: tablet per hari</small>
                    </div>
                </div>

                <hr>

                {{-- Tombol Submit --}}
                <div class="d-flex justify-content-between">
                    <a href="{{ route('data.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Simpan Data Harian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
