<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\MonteCarloController;
use App\Http\Controllers\HasilController;
use App\Http\Controllers\TentangKamiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Route Utama
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// DASHBOARD ROUTES
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ==================== ROUTES UNTUK DATA SET ====================
Route::prefix('data')->group(function () {
    Route::get('/', [DataController::class, 'index'])->name('data.index');
    Route::get('/create', [DataController::class, 'create'])->name('data.create');
    Route::post('/', [DataController::class, 'store'])->name('data.store');

    // Import / Export
    Route::get('/import', [DataController::class, 'showImportForm'])->name('data.import.form');
    Route::post('/import', [DataController::class, 'processImport'])->name('data.import.store');
    Route::get('/import/preview', [DataController::class, 'previewImport'])->name('data.import.preview');
    Route::get('/import/template', [DataController::class, 'downloadTemplate'])->name('data.import.template');
    Route::get('/export', [DataController::class, 'export'])->name('data.export');

    // Reset data
    Route::post('/clear', [DataController::class, 'clearAllData'])->name('data.clear');

    // Generate & cek template
    Route::get('/template/generate', [DataController::class, 'generateAndSaveTemplate'])->name('data.template.generate');
    Route::get('/template/check', function () {
        $filePath = public_path('templates/template_import_obat.xlsx');
        if (file_exists($filePath)) {
            return response()->json([
                'exists'        => true,
                'path'          => $filePath,
                'size'          => filesize($filePath) . ' bytes',
                'last_modified' => date('Y-m-d H:i:s', filemtime($filePath)),
            ]);
        }
        return response()->json(['exists' => false]);
    })->name('data.template.check');

    // Route CRUD sisanya
    Route::get('/{id}', [DataController::class, 'show'])->name('data.show');
    Route::get('/{id}/edit', [DataController::class, 'edit'])->name('data.edit');
    Route::put('/{id}', [DataController::class, 'update'])->name('data.update');
    Route::delete('/{id}', [DataController::class, 'destroy'])->name('data.destroy');
});

// ==================== ROUTES UNTUK MONTE CARLO ====================
// Alur: Data Set → Monte Carlo → Hasil Error / Rekap
Route::get('/monte-carlo',         [MonteCarloController::class, 'index'])->name('monte-carlo.index');
Route::post('/monte-carlo/hitung', [MonteCarloController::class, 'run'])->name('monte-carlo.run');

// ==================== ROUTES UNTUK HASIL ERROR ====================
// Hasil error & analisis / rekap
Route::get('/hasil-error', [HasilController::class, 'index'])->name('hasil.index');
Route::post('/hasil-error/show', [HasilController::class, 'show'])->name('hasil.show');
Route::get('/hasil-error/simulasi/{id_obat}/{skenario}', [HasilController::class, 'detailSimulasi'])->name('hasil.simulasi');
Route::get('/hasil-error/analisis', [HasilController::class, 'analisisKomparatif'])->name('hasil.analisis');
Route::get('/hasil-error/laporan', [HasilController::class, 'generateReport'])->name('hasil.laporan');

// ==================== ROUTE UNTUK TENTANG KAMI ====================
Route::get('/tentang-kami', [TentangKamiController::class, 'index'])->name('tentang-kami');

Route::get('/data-set', [DataController::class, 'index'])->name('data.index');

// CRUD HARIAN per tanggal (identifier = tanggal)
Route::get('/data-set/harian/{tanggal}/edit', [DataController::class, 'editHarian'])->name('data.harian.edit');
Route::put('/data-set/harian/{tanggal}', [DataController::class, 'updateHarian'])->name('data.harian.update');
Route::delete('/data-set/harian/{tanggal}', [DataController::class, 'destroyHarian'])->name('data.harian.destroy');
