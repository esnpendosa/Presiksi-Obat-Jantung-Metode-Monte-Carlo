@extends('layouts.app')

@section('title', 'Edit Data Harian')

@section('content')
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-warning">
            <strong>Edit Data Harian</strong> ({{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }})
        </div>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('data.harian.update', $tanggal) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Clopidogrel 75 mg</label>
                    <input type="number" min="0" class="form-control"
                           name="clopidogrel_75_mg"
                           value="{{ old('clopidogrel_75_mg', (int)($row->clopidogrel_75_mg ?? 0)) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Candesartan 8 mg</label>
                    <input type="number" min="0" class="form-control"
                           name="candesartan_8_mg"
                           value="{{ old('candesartan_8_mg', (int)($row->candesartan_8_mg ?? 0)) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Isosorbid Dinitrate 5 mg</label>
                    <input type="number" min="0" class="form-control"
                           name="isosorbid_dinitrate_5_mg"
                           value="{{ old('isosorbid_dinitrate_5_mg', (int)($row->isosorbid_dinitrate_5_mg ?? 0)) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Nitrokaf Retard 2.5 mg</label>
                    <input type="number" min="0" class="form-control"
                           name="nitrokaf_retard_25_mg"
                           value="{{ old('nitrokaf_retard_25_mg', (int)($row->nitrokaf_retard_25_mg ?? 0)) }}">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ route('data.index', ['tahun' => \Carbon\Carbon::parse($tanggal)->year]) }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </form>

            <hr>

            <form method="POST" action="{{ route('data.harian.destroy', $tanggal) }}"
                  onsubmit="return confirm('Yakin hapus data tanggal {{ $tanggal }}?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Hapus Tanggal Ini</button>
            </form>
        </div>
    </div>
</div>
@endsection
