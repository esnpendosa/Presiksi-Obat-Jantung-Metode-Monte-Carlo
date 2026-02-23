<?php

namespace App\Http\Controllers;

use App\Models\HasilError;
use App\Models\MetrikEvaluasi;
use App\Models\MonteCarlo;
use App\Models\Obat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HasilController extends Controller
{
    public function index(Request $request)
    {
        // daftar obat untuk dropdown
        $obats = DB::table('tb_obat')
            ->select('id_obat', 'nama_obat')
            ->orderBy('id_obat')
            ->get();

        // default: obat pertama, skenario 1
        $selectedObatId = $request->get('obat_id', $obats->first()->id_obat ?? null);
        $selectedSkenario = $request->get('skenario', 'Skenario 1');

        $metrik = null;
        $errorRows = collect();
        $ringkas = null;

        if (!$selectedObatId) {
            return view('hasil.index', compact(
                'obats',
                'selectedObatId',
                'selectedSkenario',
                'metrik',
                'errorRows',
                'ringkas'
            ));
        }

        // apakah tabel error punya kolom "tanggal"?
        $hasTanggal = DB::getSchemaBuilder()->hasColumn('tb_hasil_error', 'tanggal');

        if ($selectedSkenario === 'Skenario 1') {

            // Jika tb_hasil_error sudah ada kolom tanggal, pakai langsung
            if ($hasTanggal) {
                $errorRows = DB::table('tb_hasil_error as he')
                    ->where('he.id_obat', $selectedObatId)
                    ->where('he.skenario', $selectedSkenario)
                    ->select('he.*', DB::raw('he.tanggal as tanggal_tampil'))
                    ->orderBy('he.tanggal', 'asc')
                    ->get();
            } else {
                /**
                 * Jika belum ada kolom tanggal:
                 * - Pasangkan urutan baris tb_hasil_error dengan urutan tanggal di tb_permintaan_obat_harian
                 * - Kunci pasangan: (periode_tahun, periode_bulan, row_number)
                 */

                // Subquery error: beri nomor urut per (tahun, bulan)
                $he = DB::table('tb_hasil_error as he')
                    ->selectRaw("
                        he.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY he.periode_tahun, he.periode_bulan
                            ORDER BY he.id
                        ) as rn
                    ")
                    ->where('he.id_obat', $selectedObatId)
                    ->where('he.skenario', $selectedSkenario);

                // Subquery harian: beri nomor urut per (tahun, bulan)
                $h = DB::table('tb_permintaan_obat_harian as h')
                    ->selectRaw("
                        h.tanggal,
                        YEAR(h.tanggal)  as periode_tahun,
                        MONTH(h.tanggal) as periode_bulan,
                        ROW_NUMBER() OVER (
                            PARTITION BY YEAR(h.tanggal), MONTH(h.tanggal)
                            ORDER BY h.tanggal
                        ) as rn
                    ");

                $errorRows = DB::query()
                    ->fromSub($he, 'he')
                    ->joinSub($h, 'h', function ($join) {
                        $join->on('he.periode_tahun', '=', 'h.periode_tahun')
                            ->on('he.periode_bulan', '=', 'h.periode_bulan')
                            ->on('he.rn', '=', 'h.rn');
                    })
                    ->select('he.*', DB::raw('h.tanggal as tanggal_tampil'))
                    ->orderBy('h.tanggal', 'asc')
                    ->get();
            }

        } else {
            // Skenario 2/3: tampilkan periode (tahun-bulan) sebagai tanggal_tampil
            $errorRows = DB::table('tb_hasil_error as he')
                ->where('he.id_obat', $selectedObatId)
                ->where('he.skenario', $selectedSkenario)
                ->select(
                    'he.*',
                    DB::raw("CONCAT(he.periode_tahun, '-', LPAD(he.periode_bulan, 2, '0')) as tanggal_tampil")
                )
                ->orderBy('he.periode_tahun', 'asc')
                ->orderBy('he.periode_bulan', 'asc')
                ->get();
        }

        /**
         * FIX UTAMA:
         * Samakan perilaku index() dengan show():
         * - sinkronkan tb_metrik_evaluasi dari tb_hasil_error terbaru
         * - ambil ulang metrik setelah sinkron
         */
        $this->syncMetrikEvaluasiFromErrorRows($selectedObatId, $selectedSkenario, $errorRows);
        $metrik = $this->getMetrikWithNamaObat($selectedObatId, $selectedSkenario);

        $ringkas = $this->buildRingkasanMetrik($metrik, $errorRows);

        return view('hasil.index', compact(
            'obats',
            'selectedObatId',
            'selectedSkenario',
            'metrik',
            'errorRows',
            'ringkas'
        ));
    }

    public function show(Request $request)
    {
        // NOTE: route lama kamu pakai id_obat, skenario
        $request->validate([
            'id_obat' => 'required|exists:tb_obat,id_obat',
            'skenario' => 'required|in:Skenario 1,Skenario 2,Skenario 3',
        ]);

        $idObat = (int) $request->id_obat;
        $skenario = (string) $request->skenario;

        $obats = Obat::all();
        $obat = Obat::findOrFail($idObat);

        $errorRows = $this->getErrorRowsWithTanggalTampil($idObat, $skenario);
        $this->syncMetrikEvaluasiFromErrorRows($idObat, $skenario, $errorRows);
        $metrik = $this->getMetrikWithNamaObat($idObat, $skenario);
        $ringkas = $this->buildRingkasanMetrik($metrik, $errorRows);

        // kalau kamu masih pakai results untuk view tertentu, tetap disediakan
        $results = $this->getPredictionResults($idObat, $skenario);

        return view('hasil.index', compact(
            'obats',
            'obat',
            'results',
            'idObat',
            'skenario',
            'metrik',
            'errorRows',
            'ringkas'
        ) + [
            'selectedObatId' => $idObat,
            'selectedSkenario' => $skenario,
        ]);
    }

    public function detailSimulasi($id_obat, $skenario)
    {
        $obat = Obat::findOrFail($id_obat);

        $monteCarloData = MonteCarlo::where('id_obat', $id_obat)
            ->where('skenario', $skenario)
            ->whereNotNull('frekuensi')
            ->orderBy('id')
            ->get();

        $errorData = HasilError::where('id_obat', $id_obat)
            ->where('skenario', $skenario)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $metrics = MetrikEvaluasi::where('id_obat', $id_obat)
            ->where('skenario', $skenario)
            ->first();

        $latestPrediction = MonteCarlo::where('id_obat', $id_obat)
            ->where('skenario', $skenario)
            ->whereNotNull('simulasi_permintaan')
            ->orderBy('created_at', 'desc')
            ->first();

        $stats = [
            'total_distribusi' => $monteCarloData->count(),
            'prediksi_terakhir' => $latestPrediction ? $latestPrediction->simulasi_permintaan : 0,
            'total_error' => $errorData->count(),
            'kategori_akurasi' => $metrics ? $metrics->kategori_akurasi : 'Belum dievaluasi',
        ];

        return view('hasil.simulasi', compact(
            'obat',
            'skenario',
            'monteCarloData',
            'errorData',
            'metrics',
            'stats',
            'latestPrediction'
        ));
    }

    public function analisisKomparatif()
    {
        // Auto-backfill dari tb_hasil_error -> tb_metrik_evaluasi (MSE tetap AVG(SE), bukan RMSE)
        $this->backfillMetrikEvaluasiDariError();

        $evaluations = MetrikEvaluasi::with('obat')
            ->orderBy('id_obat')
            ->orderBy('skenario')
            ->get()
            ->groupBy('id_obat');

        $statistics = [];

        foreach ($evaluations as $idObat => $scenarios) {
            $obat = Obat::find($idObat);

            // keyBy skenario
            $bySkenario = $scenarios->keyBy(fn ($x) => trim((string)$x->skenario));

            $s1 = $bySkenario->get('Skenario 1');
            $s2 = $bySkenario->get('Skenario 2');
            $s3 = $bySkenario->get('Skenario 3');

            $best = $scenarios->sortBy('MAPE')->first();

            $statistics[] = [
                'obat' => $obat ? $obat->nama_obat : '-',
                'obat_id' => $idObat,
                's1' => $s1,
                's2' => $s2,
                's3' => $s3,
                'best' => $best,
            ];
        }

        return view('hasil.analisis', compact('statistics'));
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:pdf,excel',
            'id_obat' => 'nullable|exists:tb_obat,id_obat',
        ]);

        $format = $request->format ?? 'pdf';
        $idObat = $request->id_obat;

        $query = DB::table('tb_metrik_evaluasi as me')
            ->join('tb_obat as o', 'me.id_obat', '=', 'o.id_obat')
            ->select('o.nama_obat', 'me.*');

        if ($idObat) {
            $query->where('me.id_obat', $idObat);
        }

        $results = $query
            ->orderBy('o.nama_obat')
            ->orderBy('me.skenario')
            ->get();

        $stats = [
            'total_obat' => $results->unique('id_obat')->count(),
            'total_evaluasi' => $results->count(),
            'rata_mape' => $results->avg('MAPE'),
            'terbaik_mape' => $results->sortBy('MAPE')->first(),
            'terburuk_mape' => $results->sortByDesc('MAPE')->first(),
        ];

        return view('hasil.laporan', compact('results', 'stats', 'format', 'idObat'));
    }

    // Helpers

    private function getMetrikWithNamaObat(int $idObat, string $skenario)
    {
        return DB::table('tb_metrik_evaluasi as me')
            ->join('tb_obat as o', 'me.id_obat', '=', 'o.id_obat')
            ->select('me.*', 'o.nama_obat')
            ->where('me.id_obat', $idObat)
            ->where('me.skenario', $skenario)
            ->first();
    }

    private function getErrorRowsWithTanggalTampil(int $idObat, string $skenario)
    {
        $hasTanggal = DB::getSchemaBuilder()->hasColumn('tb_hasil_error', 'tanggal');

        if ($skenario === 'Skenario 1') {
            if ($hasTanggal) {
                return DB::table('tb_hasil_error as he')
                    ->where('he.id_obat', $idObat)
                    ->where('he.skenario', $skenario)
                    ->select('he.*', DB::raw('he.tanggal as tanggal_tampil'))
                    ->orderBy('he.tanggal', 'asc')
                    ->get();
            }

            // fallback kalau tidak ada kolom tanggal: tampilkan created_at 
            return DB::table('tb_hasil_error as he')
                ->where('he.id_obat', $idObat)
                ->where('he.skenario', $skenario)
                ->select('he.*', DB::raw('DATE(he.created_at) as tanggal_tampil'))
                ->orderBy('he.created_at', 'asc')
                ->get();
        }

        if ($skenario === 'Skenario 3') {
            // Tahunan: tampilkan tahun saja
            return DB::table('tb_hasil_error as he')
                ->where('he.id_obat', $idObat)
                ->where('he.skenario', $skenario)
                ->select('he.*', DB::raw("CAST(he.periode_tahun AS CHAR) as tanggal_tampil"))
                ->orderBy('he.periode_tahun', 'asc')
                ->get();
        }

        // Skenario 2 (bulanan): YYYY-MM
        return DB::table('tb_hasil_error as he')
            ->where('he.id_obat', $idObat)
            ->where('he.skenario', $skenario)
            ->select('he.*', DB::raw("CONCAT(he.periode_tahun, '-', LPAD(he.periode_bulan, 2, '0')) as tanggal_tampil"))
            ->orderBy('he.periode_tahun', 'asc')
            ->orderBy('he.periode_bulan', 'asc')
            ->get();
    }

    private function syncMetrikEvaluasiFromErrorRows(int $idObat, string $skenario, $errorRows): void
    {
        if (!$errorRows || $errorRows->count() === 0) return;

        $mad = (float) $errorRows->avg('AD');
        $mse = (float) $errorRows->avg('SE');   // MSE = AVG(SE)
        $mape = (float) $errorRows->avg('APE');

        DB::table('tb_metrik_evaluasi')->updateOrInsert(
            ['id_obat' => $idObat, 'skenario' => $skenario],
            [
                'MAD' => $mad,
                'MSE' => $mse,
                'MAPE' => $mape,
                'kategori_akurasi' => $this->getMapeKriteria($mape),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function backfillMetrikEvaluasiDariError(): void
    {
        $rows = DB::table('tb_hasil_error')
            ->selectRaw('id_obat, skenario, AVG(AD) as MAD, AVG(SE) as MSE, AVG(APE) as MAPE')
            ->groupBy('id_obat', 'skenario')
            ->get();

        foreach ($rows as $r) {
            $mape = (float) ($r->MAPE ?? 0);

            DB::table('tb_metrik_evaluasi')->updateOrInsert(
                ['id_obat' => $r->id_obat, 'skenario' => $r->skenario],
                [
                    'MAD' => (float) ($r->MAD ?? 0),
                    'MSE' => (float) ($r->MSE ?? 0), 
                    'MAPE' => $mape,
                    'kategori_akurasi' => $this->getMapeKriteria($mape),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function buildRingkasanMetrik($metrik, $errorRows): array
    {
        $avgMAD  = $errorRows->count() ? (float) $errorRows->avg('AD') : 0.0;
        $avgMSE  = $errorRows->count() ? (float) $errorRows->avg('SE') : 0.0;
        $avgMAPE = $errorRows->count() ? (float) $errorRows->avg('APE') : 0.0;

        // Aman kalau $metrik null
        $mad  = ($metrik && $metrik->MAD !== null) ? (float) $metrik->MAD : $avgMAD;
        $mse  = ($metrik && $metrik->MSE !== null) ? (float) $metrik->MSE : $avgMSE;
        $mape = ($metrik && $metrik->MAPE !== null) ? (float) $metrik->MAPE : $avgMAPE;

        // RMSE dihitung dari MSE (untuk tampilan)
        $rmse = $mse > 0 ? sqrt($mse) : 0.0;

        $kategori = ($metrik && !empty($metrik->kategori_akurasi))
            ? $metrik->kategori_akurasi
            : $this->getMapeKriteria($mape);

        return [
            'mad' => $mad,
            'mse' => $mse,
            'rmse' => $rmse,
            'mape' => $mape,
            'kriteria' => $kategori,
        ];
    }

    // kriteria nilai mape 
    private function getMapeKriteria(float $mape): string
    {
        if ($mape <= 10) return 'Sangat Baik';
        if ($mape <= 20) return 'Baik';
        if ($mape <= 50) return 'Cukup';
        return 'Buruk';
    }

    private function getPredictionResults($idObat, $skenario)
    {
        return DB::table('tb_monte_carlo as mc')
            ->join('tb_obat as o', 'mc.id_obat', '=', 'o.id_obat')
            ->leftJoin('tb_hasil_error as he', function ($join) use ($idObat, $skenario) {
                $join->on('mc.id_obat', '=', 'he.id_obat')
                    ->on('mc.skenario', '=', 'he.skenario')
                    ->where('he.id_obat', $idObat)
                    ->where('he.skenario', $skenario);
            })
            ->leftJoin('tb_metrik_evaluasi as me', function ($join) use ($idObat, $skenario) {
                $join->on('mc.id_obat', '=', 'me.id_obat')
                    ->on('mc.skenario', '=', 'me.skenario')
                    ->where('me.id_obat', $idObat)
                    ->where('me.skenario', $skenario);
            })
            ->where('mc.id_obat', $idObat)
            ->where('mc.skenario', $skenario)
            ->whereNotNull('mc.simulasi_permintaan')
            ->select(
                'o.nama_obat',
                'mc.skenario',
                'mc.simulasi_permintaan as prediksi',
                'he.data_aktual',
                'he.AD',
                'he.SE',
                'he.APE',
                'me.MAPE',
                'me.MSE',
                'me.MAD',
                'me.kategori_akurasi',
                'mc.created_at as tanggal_prediksi'
            )
            ->orderBy('mc.created_at', 'desc')
            ->get();
    }
}