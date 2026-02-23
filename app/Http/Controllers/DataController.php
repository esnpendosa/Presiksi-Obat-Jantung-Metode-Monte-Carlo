<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obat;
use App\Models\PeriodePermintaan;
use App\Models\MonteCarlo;
use App\Models\HasilError;
use App\Models\MetrikEvaluasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataController extends Controller
{
    /**
     * Mapping nama obat -> nama kolom di database
     */
    private $obatMapping = [
        'Clopidogrel 75 Mg'        => 'clopidogrel_75_mg',
        'Candesartan 8 Mg'         => 'candesartan_8_mg',
        'Isosorbid Dinitrate 5 Mg' => 'isosorbid_dinitrate_5_mg',
        'Nitrokaf Retard 2.5 Mg'  => 'nitrokaf_retard_25_mg',
    ];

    // =================================================================
    //  INDEX – TAMPILKAN DATASET (HARIAN) + RINGKASAN
    // =================================================================


    public function index(Request $request)
    {
        // ambil tahun dari query string: /data-set?tahun=2024
        $tahun = $request->query('tahun');

        // daftar obat (untuk header tabel)
        $obats = Obat::orderBy('id_obat')->get();

        // mapping id_obat => nama kolom (dipakai blade: $columnMapById)
        $columnMapById = [];
        foreach ($obats as $obat) {
            $columnMapById[$obat->id_obat] = $this->getColumnName($obat->nama_obat);
        }

        // daftar tahun yang tersedia (untuk dropdown)
        $availableYears = collect();
        if (Schema::hasTable('tb_permintaan_obat_harian')) {
            $availableYears = DB::table('tb_permintaan_obat_harian')
                ->selectRaw('YEAR(tanggal) as tahun')
                ->whereNotNull('tanggal')
                ->distinct()
                ->orderBy('tahun', 'desc')
                ->pluck('tahun');
        } elseif (Schema::hasTable('tb_periode_permintaan')) {
            $availableYears = PeriodePermintaan::select('periode_tahun')
                ->distinct()
                ->orderBy('periode_tahun', 'desc')
                ->pluck('periode_tahun');
        }

        // kalau user belum pilih tahun, default ke tahun terbaru yang ada
        $showAll = ($tahun === 'all');

        if (!$showAll) {
                    if ($tahun !== null && $tahun !== '') {
            $selectedYear = (int) $tahun;
        } elseif ($availableYears->count() > 0) {
            $selectedYear = (int) $availableYears->first();
        }
        }
        $selectedYear = $tahun ? (int) $tahun : null;

        // DATA HARIAN
        $detailHarian = collect();
        if (Schema::hasTable('tb_permintaan_obat_harian')) {
            $q = DB::table('tb_permintaan_obat_harian')->orderBy('tanggal', 'asc');

            if ($selectedYear) {
                $q->whereYear('tanggal', $selectedYear);
            }

            $detailHarian = $q->get();
        }

        // DATA BULANAN
        $periodes = collect();
        if (Schema::hasTable('tb_periode_permintaan')) {
            $q = PeriodePermintaan::orderBy('periode_tahun', 'asc')
                ->orderBy('periode_bulan', 'asc');

            if ($selectedYear) {
                $q->where('periode_tahun', $selectedYear);
            }

            $periodes = $q->get();
        }

        // Total permintaan per obat (ikuti filter tahun)
        $totalPermintaan = [];
        foreach ($obats as $obat) {
            $columnName = $this->getColumnName($obat->nama_obat);

            if (
                Schema::hasTable('tb_permintaan_obat_harian') &&
                Schema::hasColumn('tb_permintaan_obat_harian', $columnName)
            ) {
                $q = DB::table('tb_permintaan_obat_harian');

                if ($selectedYear) {
                    $q->whereYear('tanggal', $selectedYear);
                }

                $totalPermintaan[$obat->id_obat] = (int) $q->sum($columnName);
            } else {
                $totalPermintaan[$obat->id_obat] = 0;
            }
        }

        return view('data-set', compact(
            'obats',
            'periodes',
            'detailHarian',
            'totalPermintaan',
            'columnMapById',
            'availableYears',
            'selectedYear'
        ));
    }

    // =================================================================
    //  MONTE CARLO – PREDIKSI (PAKAI DATA BULANAN HASIL AKUMULASI HARIAN)
    // =================================================================

    public function processPrediction(Request $request)
    {
        $request->validate([
            'obat_id'          => 'required|exists:tb_obat,id_obat',
            'skenario'         => 'required|in:Skenario 1,Skenario 2,Skenario 3',
            'jumlah_simulasi'  => 'nullable|integer|min:100|max:10000',
            'periode_prediksi' => 'nullable|integer|min:1|max:12',
        ]);

        try {
            $idObat          = $request->obat_id;
            $skenario        = $request->skenario;
            $jumlahSimulasi  = $request->jumlah_simulasi ?? 1000;
            $periodePrediksi = $request->periode_prediksi ?? 3;

            $obat = Obat::findOrFail($idObat);

            Log::info('Memproses prediksi untuk obat:', [
                'id'          => $idObat,
                'nama'        => $obat->nama_obat,
                'column_name' => $this->getColumnName($obat->nama_obat),
            ]);

            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')->insert([
                    'proses'     => 'Prediksi Monte Carlo - ' . $obat->nama_obat,
                    'status'     => 'PROCESSING',
                    'pesan'      => 'Memulai prediksi untuk: ' . $obat->nama_obat,
                    'created_at' => now(),
                ]);
            }

            // Ambil data historis BULANAN (hasil akumulasi dari harian)
            $columnName = $this->getColumnName($obat->nama_obat);
            if (empty($columnName)) {
                throw new \Exception('Kolom database tidak ditemukan untuk obat: ' . $obat->nama_obat);
            }
            if (!Schema::hasColumn('tb_periode_permintaan', $columnName)) {
                throw new \Exception('Kolom ' . $columnName . ' tidak ditemukan di tabel tb_periode_permintaan');
            }

            $dataHistoris = PeriodePermintaan::whereNotNull($columnName)
                ->where($columnName, '>', 0)
                ->orderBy('periode_tahun', 'asc')
                ->orderBy('periode_bulan', 'asc')
                ->pluck($columnName)
                ->toArray();

            if (count($dataHistoris) < 3) {
                throw new \Exception(
                    'Data historis minimal 3 periode diperlukan untuk prediksi. Data tersedia: '
                        . count($dataHistoris) . ' periode'
                );
            }

            $prediksi = $this->runMonteCarloSimulationProposal(
                $idObat,
                $obat->nama_obat,
                $skenario,
                $dataHistoris,
                $jumlahSimulasi,
                $periodePrediksi
            );

            $dataAktual   = end($dataHistoris);
            $errorMetrics = $this->calculateErrorProposal($idObat, $skenario, $prediksi, $dataAktual);

            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')
                    ->where('proses', 'LIKE', '%Prediksi Monte Carlo - ' . $obat->nama_obat . '%')
                    ->orderBy('id_log', 'desc')
                    ->limit(1)
                    ->update([
                        'status' => 'SUCCESS',
                        'pesan'  => 'Prediksi berhasil: ' . $prediksi . ' unit',
                    ]);
            }

            return redirect()->route('monte-carlo.results', [
                'id_obat'  => $idObat,
                'skenario' => $skenario,
            ])
                ->with('success', 'Prediksi Monte Carlo berhasil diproses!')
                ->with('prediksi', $prediksi);
        } catch (\Exception $e) {

            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')->insert([
                    'proses'     => 'Prediksi Monte Carlo',
                    'status'     => 'ERROR',
                    'pesan'      => 'Error: ' . $e->getMessage(),
                    'created_at' => now(),
                ]);
            }

            Log::error('Error prediksi:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('data.index')
                ->with('error', 'Gagal memproses prediksi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Helper: nama kolom dari nama obat.
     */
    private function getColumnName($namaObat)
    {
        $namaObat = trim($namaObat);

        if (isset($this->obatMapping[$namaObat])) {
            return $this->obatMapping[$namaObat];
        }

        $columnName = strtolower($namaObat);
        $columnName = str_replace([' ', '-', '.'], '_', $columnName);
        $columnName = preg_replace('/[^a-z0-9_]/', '', $columnName);

        return $columnName;
    }

    /**
     * Implementasi Monte Carlo 
     */
    private function runMonteCarloSimulationProposal(
        $idObat,
        $namaObat,
        $skenario,
        $dataHistoris,
        $jumlahSimulasi,
        $periodePrediksi
    ) {
        $frekuensiData = array_count_values($dataHistoris);
        ksort($frekuensiData);

        $totalData      = count($dataHistoris);
        $distribusiData = [];

        Log::info('Data historis untuk simulasi:', [
            'total_data' => $totalData,
            'nilai_unik' => array_keys($frekuensiData),
            'frekuensi'  => array_values($frekuensiData),
        ]);

        foreach ($frekuensiData as $nilai => $freq) {
            $probabilitas = $freq / $totalData;
            $distribusiData[$nilai] = [
                'nilai'        => $nilai,
                'frekuensi'    => $freq,
                'probabilitas' => $probabilitas,
            ];
        }

        $kumulatif = 0;
        foreach ($distribusiData as $nilai => &$data) {
            $kumulatif        += $data['probabilitas'];
            $data['kumulatif'] = $kumulatif;
        }
        unset($data);

        $intervalData  = [];
        $prevKumulatif = 0;
        foreach ($distribusiData as $nilai => $data) {
            $intervalAwal  = floor($prevKumulatif * 10000) + 1;
            $intervalAkhir = floor($data['kumulatif'] * 10000);

            if ($intervalAwal < 1) $intervalAwal = 1;
            if ($intervalAkhir < 1) $intervalAkhir = 1;
            if ($intervalAkhir > 10000) $intervalAkhir = 10000;

            $intervalData[] = [
                'nilai'          => $nilai,
                'interval_awal'  => (int) $intervalAwal,
                'interval_akhir' => (int) $intervalAkhir,
            ];

            $prevKumulatif = $data['kumulatif'];
        }

        $hasilSimulasi = [];
        for ($i = 0; $i < $jumlahSimulasi; $i++) {
            $angkaAcak = mt_rand(1, 10000);
            foreach ($intervalData as $interval) {
                if (
                    $angkaAcak >= $interval['interval_awal'] &&
                    $angkaAcak <= $interval['interval_akhir']
                ) {
                    $hasilSimulasi[] = $interval['nilai'];
                    break;
                }
            }
        }

        $prediksiRata = array_sum($hasilSimulasi) / count($hasilSimulasi);
        $prediksi     = round($prediksiRata * $periodePrediksi);

        $this->saveSimulationData($idObat, $skenario, $distribusiData, $intervalData, $prediksi, $hasilSimulasi);

        return $prediksi;
    }

    /**
     * Simpan data simulasi ke database.
     */
    private function saveSimulationData($idObat, $skenario, $distribusiData, $intervalData, $prediksi, $hasilSimulasi)
    {
        // tabel distribusi Monte Carlo
        if (Schema::hasTable('tb_monte_carlo')) {
            DB::table('tb_monte_carlo')
                ->where('id_obat', $idObat)
                ->where('skenario', $skenario)
                ->delete();

            foreach ($distribusiData as $nilai => $data) {
                $intervalKey = array_search($nilai, array_column($intervalData, 'nilai'));
                $interval    = $intervalKey !== false ? $intervalData[$intervalKey] : null;

                $row = [
                    'id_obat'                 => $idObat,
                    'skenario'                => $skenario,
                    'nilai_frekuensi'         => $data['nilai'],
                    'frekuensi'               => $data['frekuensi'],
                    'distribusi_probabilitas' => $data['probabilitas'],
                    'distribusi_kumulatif'    => $data['kumulatif'],
                    'simulasi_permintaan'     => $prediksi,
                    'interval_awal'           => $interval ? $interval['interval_awal'] : null,
                    'interval_akhir'          => $interval ? $interval['interval_akhir'] : null,
                    'created_at'              => now(),
                    'updated_at'              => now(),
                ];

                DB::table('tb_monte_carlo')->insert($row);
            }
        }

        // tabel hasil_simulasi (statistik)
        if (Schema::hasTable('tb_hasil_simulasi')) {
            $statistik = [
                'jumlah_simulasi' => count($hasilSimulasi),
                'rata_rata'       => array_sum($hasilSimulasi) / count($hasilSimulasi),
                'min'             => min($hasilSimulasi),
                'max'             => max($hasilSimulasi),
                'standar_deviasi' => $this->calculateStandardDeviation($hasilSimulasi),
            ];

            DB::table('tb_hasil_simulasi')->insert([
                'id_obat'   => $idObat,
                'skenario'  => $skenario,
                'statistik' => json_encode($statistik),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Hitung error (MAD, MSE, MAPE) dan simpan.
     */
    private function calculateErrorProposal($idObat, $skenario, $prediksi, $dataAktual)
    {
        $mad  = abs($prediksi - $dataAktual);
        $mse  = pow($prediksi - $dataAktual, 2);
        $mape = $dataAktual > 0 ? abs(($prediksi - $dataAktual) / $dataAktual) * 100 : 0;

        if (Schema::hasTable('tb_hasil_error')) {
            DB::table('tb_hasil_error')->insert([
                'id_obat'       => $idObat,
                'skenario'      => $skenario,
                'data_prediksi' => $prediksi,
                'data_aktual'   => $dataAktual,
                'AD'            => $mad,
                'SE'            => $mse,
                'APE'           => $mape,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $this->updateEvaluationMetrics($idObat, $skenario);

        return [
            'MAD'  => $mad,
            'MSE'  => $mse,
            'MAPE' => $mape,
        ];
    }

    /**
     * Update metrik evaluasi (tb_metrik_evaluasi).
     */
    private function updateEvaluationMetrics($idObat, $skenario)
    {
        if (!Schema::hasTable('tb_hasil_error') || !Schema::hasTable('tb_metrik_evaluasi')) {
            return;
        }

        $errors = DB::table('tb_hasil_error')
            ->where('id_obat', $idObat)
            ->where('skenario', $skenario)
            ->get();

        if ($errors->count() > 0) {
            $avgMAD  = $errors->avg('AD');
            $avgMSE  = $errors->avg('SE');
            $avgMAPE = $errors->avg('APE');

            $kategori = $this->getAccuracyCategory($avgMAPE);

            DB::table('tb_metrik_evaluasi')->updateOrInsert(
                ['id_obat' => $idObat, 'skenario' => $skenario],
                [
                    'MAD'              => $avgMAD,
                    'MSE'              => $avgMSE,
                    'MAPE'             => $avgMAPE,
                    'kategori_akurasi' => $kategori,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]
            );
        }
    }

    private function getAccuracyCategory($mape)
    {
        if ($mape < 10) return 'Sangat Baik';
        if ($mape < 20) return 'Baik';
        if ($mape < 50) return 'Cukup';
        return 'Buruk';
    }

    private function calculateStandardDeviation($data)
    {
        if (count($data) < 2) return 0;

        $mean       = array_sum($data) / count($data);
        $sumSquares = 0;

        foreach ($data as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return sqrt($sumSquares / (count($data) - 1));
    }

    // =========================
    //  CRUD & INPUT MANUAL
    // =========================

    public function create()
    {
        $obats = Obat::all();
        return view('data.create', compact('obats'));
    }

    public function store(Request $request)
    {
        // Validasi input harian
        $request->validate([
            'tanggal_permintaan'        => 'required|date',
            'clopidogrel_75_mg'         => 'nullable|integer|min:0',
            'nitrokaf_retard_25_mg'    => 'nullable|integer|min:0',
            'isosorbid_dinitrate_5_mg'  => 'nullable|integer|min:0',
            'candesartan_8_mg'          => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            $tanggal = Carbon::parse($request->tanggal_permintaan)->startOfDay();

            // Normalisasi nilai (kalau null jadikan 0)
            $clopi = (int) ($request->clopidogrel_75_mg        ?? 0);
            $nitro = (int) ($request->nitrokaf_retard_25_mg   ?? 0);
            $iso   = (int) ($request->isosorbid_dinitrate_5_mg ?? 0);
            $cande = (int) ($request->candesartan_8_mg         ?? 0);

            // 1. SIMPAN / UPDATE DATA HARIAN
            if (Schema::hasTable('tb_permintaan_obat_harian')) {
                // upsert berdasarkan tanggal (satu tanggal hanya satu baris)
                DB::table('tb_permintaan_obat_harian')->updateOrInsert(
                    ['tanggal' => $tanggal->toDateString()],
                    [
                        'clopidogrel_75_mg'        => $clopi,
                        'nitrokaf_retard_25_mg'   => $nitro,
                        'isosorbid_dinitrate_5_mg' => $iso,
                        'candesartan_8_mg'         => $cande,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]
                );
            }

            // 2. HITUNG ULANG AKUMULASI BULANAN DARI DATA HARIAN UNTUK BULAN & TAHUN INI
            $year  = $tanggal->year;
            $month = $tanggal->month;

            if (Schema::hasTable('tb_permintaan_obat_harian')) {
                $sum = DB::table('tb_permintaan_obat_harian')
                    ->selectRaw("
                    SUM(clopidogrel_75_mg)        as clopi,
                    SUM(candesartan_8_mg)         as cande,
                    SUM(isosorbid_dinitrate_5_mg) as iso,
                    SUM(nitrokaf_retard_25_mg)   as nitro
                ")
                    ->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month)
                    ->first();

                // Buat / update baris di tb_periode_permintaan
                $periode = PeriodePermintaan::updateOrCreate(
                    [
                        'periode_tahun' => $year,
                        'periode_bulan' => $month,
                    ],
                    [
                        'clopidogrel_75_mg'        => (int) ($sum->clopi  ?? 0),
                        'candesartan_8_mg'         => (int) ($sum->cande  ?? 0),
                        'isosorbid_dinitrate_5_mg' => (int) ($sum->iso    ?? 0),
                        'nitrokaf_retard_25_mg'   => (int) ($sum->nitro  ?? 0),
                        'updated_at'               => now(),
                    ]
                );
            }

            DB::commit();

            return redirect()->route('data.index')
                ->with('success', 'Data permintaan obat harian berhasil disimpan dan diakumulasi per bulan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data harian: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function editHarian(string $tanggal)
    {
        if (!Schema::hasTable('tb_permintaan_obat_harian')) {
            return redirect()->route('data.index')->with('error', 'Tabel tb_permintaan_obat_harian belum dibuat.');
        }

        $row = DB::table('tb_permintaan_obat_harian')->where('tanggal', $tanggal)->first();
        if (!$row) {
            return redirect()->route('data.index')->with('error', 'Data harian tidak ditemukan untuk tanggal: ' . $tanggal);
        }

        // form edit paling gampang: reuse view create tapi isi default (kalau view-mu mendukung)
        // kalau belum, buat view baru: resources/views/data/edit-harian.blade.php
        return view('data.edit-harian', [
            'tanggal' => $tanggal,
            'row' => $row,
        ]);
    }

    public function updateHarian(Request $request, string $tanggal)
    {
        $request->validate([
            'clopidogrel_75_mg'        => 'nullable|integer|min:0',
            'nitrokaf_retard_25_mg'   => 'nullable|integer|min:0',
            'isosorbid_dinitrate_5_mg' => 'nullable|integer|min:0',
            'candesartan_8_mg'         => 'nullable|integer|min:0',
        ]);

        if (!Schema::hasTable('tb_permintaan_obat_harian')) {
            return redirect()->route('data.index')->with('error', 'Tabel tb_permintaan_obat_harian belum dibuat.');
        }

        DB::beginTransaction();
        try {
            DB::table('tb_permintaan_obat_harian')
                ->where('tanggal', $tanggal)
                ->update([
                    'clopidogrel_75_mg'        => (int)($request->clopidogrel_75_mg ?? 0),
                    'nitrokaf_retard_25_mg'   => (int)($request->nitrokaf_retard_25_mg ?? 0),
                    'isosorbid_dinitrate_5_mg' => (int)($request->isosorbid_dinitrate_5_mg ?? 0),
                    'candesartan_8_mg'         => (int)($request->candesartan_8_mg ?? 0),
                    'updated_at'               => now(),
                ]);

            // hitung ulang bulanan untuk bulan & tahun tanggal 
            $dt = \Carbon\Carbon::parse($tanggal);
            $year = $dt->year;
            $month = $dt->month;

            if (Schema::hasTable('tb_periode_permintaan')) {
                $sum = DB::table('tb_permintaan_obat_harian')
                    ->selectRaw("
                    SUM(clopidogrel_75_mg) as clopi,
                    SUM(candesartan_8_mg) as cande,
                    SUM(isosorbid_dinitrate_5_mg) as iso,
                    SUM(nitrokaf_retard_25_mg) as nitro
                ")
                    ->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month)
                    ->first();

                PeriodePermintaan::updateOrCreate(
                    ['periode_tahun' => $year, 'periode_bulan' => $month],
                    [
                        'clopidogrel_75_mg'        => (int)($sum->clopi ?? 0),
                        'candesartan_8_mg'         => (int)($sum->cande ?? 0),
                        'isosorbid_dinitrate_5_mg' => (int)($sum->iso ?? 0),
                        'nitrokaf_retard_25_mg'   => (int)($sum->nitro ?? 0),
                        'updated_at'               => now(),
                    ]
                );
            }

            DB::commit();

            // biar balik ke filter tahun yang sama
            return redirect()->route('data.index', ['tahun' => $year])
                ->with('success', 'Data harian berhasil diperbarui: ' . $tanggal);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal update data harian: ' . $e->getMessage());
        }
    }

    public function destroyHarian(string $tanggal)
    {
        if (!Schema::hasTable('tb_permintaan_obat_harian')) {
            return redirect()->route('data.index')->with('error', 'Tabel tb_permintaan_obat_harian belum dibuat.');
        }

        DB::beginTransaction();
        try {
            DB::table('tb_permintaan_obat_harian')->where('tanggal', $tanggal)->delete();

            // hitung ulang bulanan untuk bulan & tahun tanggal yang dihapus
            $dt = \Carbon\Carbon::parse($tanggal);
            $year = $dt->year;
            $month = $dt->month;

            if (Schema::hasTable('tb_periode_permintaan')) {
                $sum = DB::table('tb_permintaan_obat_harian')
                    ->selectRaw("
                    SUM(clopidogrel_75_mg) as clopi,
                    SUM(candesartan_8_mg) as cande,
                    SUM(isosorbid_dinitrate_5_mg) as iso,
                    SUM(nitrokaf_retard_25_mg) as nitro
                ")
                    ->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month)
                    ->first();

                // kalau 1 bulan jadi 0 semua, kamu bisa pilih: update jadi 0 atau delete baris bulanan
                PeriodePermintaan::updateOrCreate(
                    ['periode_tahun' => $year, 'periode_bulan' => $month],
                    [
                        'clopidogrel_75_mg'        => (int)($sum->clopi ?? 0),
                        'candesartan_8_mg'         => (int)($sum->cande ?? 0),
                        'isosorbid_dinitrate_5_mg' => (int)($sum->iso ?? 0),
                        'nitrokaf_retard_25_mg'   => (int)($sum->nitro ?? 0),
                        'updated_at'               => now(),
                    ]
                );
            }

            DB::commit();

            return redirect()->route('data.index', ['tahun' => $year])
                ->with('success', 'Data harian berhasil dihapus: ' . $tanggal);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('data.index')->with('error', 'Gagal hapus data harian: ' . $e->getMessage());
        }
    }

    // =================================================================
    //  IMPORT HARIAN LANGSUNG DARI EXCEL TEMPLATE
    // =================================================================

    public function showImportForm()
    {
        return view('data.import');
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $file = $request->file('excelFile');

            // Hapus data lama jika diminta
            if ($request->has('overwriteData') && $request->overwriteData === 'on') {
                if (Schema::hasTable('tb_permintaan_obat_harian')) {
                    DB::table('tb_permintaan_obat_harian')->truncate();
                }
                if (Schema::hasTable('tb_periode_permintaan')) {
                    DB::table('tb_periode_permintaan')->truncate();
                }
            }

            if (!Schema::hasTable('tb_permintaan_obat_harian')) {
                throw new \Exception('Tabel tb_permintaan_obat_harian belum dibuat.');
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();

            $inserted = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $rawDate = $sheet->getCell('A' . $row)->getValue();
                if ($rawDate === null || $rawDate === '') {
                    continue;
                }

                try {
                    if (is_numeric($rawDate)) {
                        $dateObj = ExcelDate::excelToDateTimeObject($rawDate);
                        $tanggal = $dateObj->format('Y-m-d');
                    } else {
                        $rawDate = trim((string) $rawDate);
                        // format di template: dd/mm/yyyy
                        $tanggal = Carbon::createFromFormat('d/m/Y', $rawDate)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    Log::warning('Gagal parse tanggal di baris ' . $row . ': ' . $rawDate);
                    continue;
                }

                $clopi = (int) $sheet->getCell('B' . $row)->getCalculatedValue();
                $nitro = (int) $sheet->getCell('C' . $row)->getCalculatedValue();
                $iso   = (int) $sheet->getCell('D' . $row)->getCalculatedValue();
                $cande = (int) $sheet->getCell('E' . $row)->getCalculatedValue();

                if ($clopi === 0 && $nitro === 0 && $iso === 0 && $cande === 0) {
                    continue;
                }

                DB::table('tb_permintaan_obat_harian')->updateOrInsert(
                    ['tanggal' => $tanggal],
                    [
                        'clopidogrel_75_mg'        => $clopi,
                        'nitrokaf_retard_25_mg'   => $nitro,
                        'isosorbid_dinitrate_5_mg' => $iso,
                        'candesartan_8_mg'         => $cande,
                        'created_at'               => now(),
                        'updated_at'               => now(),
                    ]
                );

                $inserted++;
            }

            // BENTUK DATA BULANAN DARI HARIAN (untuk Monte Carlo)
            if (Schema::hasTable('tb_periode_permintaan')) {
                DB::table('tb_periode_permintaan')->truncate();

                $harianAgg = DB::table('tb_permintaan_obat_harian')
                    ->selectRaw("
                        YEAR(tanggal) as periode_tahun,
                        MONTH(tanggal) as periode_bulan,
                        SUM(clopidogrel_75_mg) as clopidogrel_75_mg,
                        SUM(candesartan_8_mg) as candesartan_8_mg,
                        SUM(isosorbid_dinitrate_5_mg) as isosorbid_dinitrate_5_mg,
                        SUM(nitrokaf_retard_25_mg) as nitrokaf_retard_25_mg
                    ")
                    ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
                    ->orderBy('periode_tahun')
                    ->orderBy('periode_bulan')
                    ->get();

                foreach ($harianAgg as $row) {
                    PeriodePermintaan::create([
                        'periode_tahun'            => $row->periode_tahun,
                        'periode_bulan'            => $row->periode_bulan,
                        'clopidogrel_75_mg'        => $row->clopidogrel_75_mg,
                        'candesartan_8_mg'         => $row->candesartan_8_mg,
                        'isosorbid_dinitrate_5_mg' => $row->isosorbid_dinitrate_5_mg,
                        'nitrokaf_retard_25_mg'   => $row->nitrokaf_retard_25_mg,
                    ]);
                }
            }

            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')->insert([
                    'proses'     => 'Import Data Excel',
                    'status'     => 'SUCCESS',
                    'pesan'      => 'Import data harian berhasil diselesaikan.',
                    'created_at' => now(),
                ]);
            }

            return redirect()->route('data.index')
                ->with('success', 'Data harian berhasil diimport dari Excel. Baris tersimpan: ' . $inserted);
        } catch (\Exception $e) {
            Log::error('Import error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')->insert([
                    'proses'     => 'Import Data Excel',
                    'status'     => 'ERROR',
                    'pesan'      => 'Error: ' . $e->getMessage(),
                    'created_at' => now(),
                ]);
            }

            return redirect()->back()
                ->with('error', 'Gagal mengimport data: ' . $e->getMessage())
                ->withInput();
        }
    }

    //  TEMPLATE 

    public function generateAndSaveTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();

            $sheet->setTitle('Data Permintaan Obat Harian');

            $headers = [
                'Tanggal (dd/mm/yyyy)',
                'CLOPIDOGREL 75 MG TABL.',
                'NITROKAF RETARD 2,5 Mg Tabl',
                'ISOSORBID DINITRATE (ISDN) 5 MG TABL.',
                'CANDESARTAN 8 MG TABL.',
            ];

            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            }

            $headerStyle = [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E75B6'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'FFFFFF'],
                    ],
                ],
            ];

            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(30);

            foreach (range('A', 'E') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $templateDir = public_path('templates');
            if (!file_exists($templateDir)) {
                mkdir($templateDir, 0755, true);
            }

            $writer   = new Xlsx($spreadsheet);
            $fileName = 'template_import_obat.xlsx';
            $filePath = $templateDir . '/' . $fileName;

            $writer->save($filePath);

            Log::info('Template berhasil dibuat di: ' . $filePath);

            return response()->json([
                'success'   => true,
                'message'   => 'Template berhasil dibuat dan disimpan',
                'file_path' => $filePath,
                'file_url'  => url('templates/' . $fileName),
            ]);
        } catch (\Exception $e) {
            Log::error('Error membuat template:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat template: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $fileName = 'template_import_obat.xlsx';
        $filePath = public_path('templates/' . $fileName);

        if (!file_exists($filePath)) {
            $result = $this->generateAndSaveTemplate();
            $data   = $result->getData();
            if (empty($data->success) || !$data->success) {
                return redirect()->back()->with('error', 'Gagal membuat template: ' . $data->message);
            }
        }

        if (file_exists($filePath)) {
            return response()->download($filePath, 'template_import_obat_' . date('Ymd') . '.xlsx');
        }

        return redirect()->back()->with('error', 'Template tidak ditemukan.');
    }

    // =================================================================
    //  CLEAR ALL DATA – TANPA TRANSACTION (tidak akan ada error PDO)
    // =================================================================

    public function clearAllData()
    {
        try {
            // matikan FK sementara (MySQL)
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            } catch (\Throwable $e) {
                // abaikan
            }

            if (Schema::hasTable('tb_permintaan_obat_harian')) {
                DB::table('tb_permintaan_obat_harian')->truncate();
            }
            if (Schema::hasTable('tb_periode_permintaan')) {
                DB::table('tb_periode_permintaan')->truncate();
            }
            if (Schema::hasTable('tb_monte_carlo')) {
                DB::table('tb_monte_carlo')->truncate();
            }
            if (Schema::hasTable('tb_hasil_error')) {
                DB::table('tb_hasil_error')->truncate();
            }
            if (Schema::hasTable('tb_metrik_evaluasi')) {
                DB::table('tb_metrik_evaluasi')->truncate();
            }
            if (Schema::hasTable('tb_log_proses')) {
                DB::table('tb_log_proses')->truncate();
            }
            if (Schema::hasTable('tb_hasil_simulasi')) {
                DB::table('tb_hasil_simulasi')->truncate();
            }

            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                // abaikan
            }

            return redirect()->route('data.index')
                ->with('success', 'Semua data berhasil direset.');
        } catch (\Throwable $e) {
            return redirect()->route('data.index')
                ->with('error', 'Gagal mereset data: ' . $e->getMessage());
        }
    }

    // =================================================================
    //  FUNGSI SHOW/EDIT/UPDATE/DESTROY PERIODE (opsional)
    // =================================================================

    public function show($id)
    {
        $periode = PeriodePermintaan::findOrFail($id);
        $obats   = Obat::all();

        $dataObat = [];
        foreach ($obats as $obat) {
            $columnName = $this->getColumnName($obat->nama_obat);
            if ($columnName && Schema::hasColumn('tb_periode_permintaan', $columnName)) {
                $dataObat[$obat->id_obat] = [
                    'nama'   => $obat->nama_obat,
                    'jumlah' => $periode->{$columnName} ?? 0,
                ];
            } else {
                $dataObat[$obat->id_obat] = [
                    'nama'   => $obat->nama_obat,
                    'jumlah' => 0,
                ];
            }
        }

        return view('data.show', compact('periode', 'obats', 'dataObat'));
    }

    public function edit($id)
    {
        $periode = PeriodePermintaan::findOrFail($id);
        $obats   = Obat::all();

        return view('data.edit', compact('periode', 'obats'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_obat'           => 'required|exists:tb_obat,id_obat',
            'jumlah_permintaan' => 'required|integer|min:0',
        ]);

        try {
            $periode = PeriodePermintaan::findOrFail($id);
            $obat    = Obat::findOrFail($request->id_obat);

            $columnName = $this->getColumnName($obat->nama_obat);

            if (!empty($columnName) && Schema::hasColumn('tb_periode_permintaan', $columnName)) {
                $periode->{$columnName} = $request->jumlah_permintaan;
                $periode->save();
            }

            return redirect()->route('data.index')
                ->with('success', 'Data berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $periode = PeriodePermintaan::findOrFail($id);
            $periode->delete();

            return redirect()->route('data.index')
                ->with('success', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('data.index')
                ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}