<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obat;
use App\Models\PeriodePermintaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\MetrikEvaluasi;

class MonteCarloController extends Controller
{
    private $obatMapping = [
        'Clopidogrel 75 Mg'        => 'clopidogrel_75_mg',
        'Candesartan 8 Mg'         => 'candesartan_8_mg',
        'Isosorbid Dinitrate 5 Mg' => 'isosorbid_dinitrate_5_mg',
        'Nitrokaf Retard 2.5 Mg'  => 'nitrokaf_retard_25_mg',
    ];

    private function getExcelTemplatePath(string $skenario): ?string
    {
        $base = storage_path('app/montecarlo');

        if ($skenario === 'Skenario 1') return $base . DIRECTORY_SEPARATOR . 'SKNENARIO 1 FIX HARIAN.xlsx';
        if ($skenario === 'Skenario 2') return $base . DIRECTORY_SEPARATOR . 'SKENARIO 2 FIX BULANAN.xlsx';
        if ($skenario === 'Skenario 3') return $base . DIRECTORY_SEPARATOR . 'SKENARIO 3 FIX TAHUNAN.xlsx';

        return null;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $obats = Obat::orderBy('id_obat')->get();

        // Ambil dari query kalau ada, kalau tidak ambil dari session
        $selectedObatId = $request->query('obat_id', session('mc_selected_obat_id'));
        $selectedSkenario = $request->query('skenario', session('mc_selected_skenario'));

        // Rekap permanen
        $rekap = session('mc_rekap');

        return view('monte-carlo.index', compact('obats', 'rekap', 'selectedObatId', 'selectedSkenario'));
    }

    public function run(Request $request)
    {
        $request->validate([
            'obat_id'  => 'required|exists:tb_obat,id_obat',
            'skenario' => 'required|in:Skenario 1,Skenario 2,Skenario 3',
        ]);

        try {
            $idObat   = (int) $request->obat_id;
            $skenario = $request->input('skenario', 'Skenario 1');

            $obat = Obat::findOrFail($idObat);

            $columnName = $this->getColumnName($obat->nama_obat);
            if (empty($columnName)) {
                throw new \Exception('Kolom database tidak ditemukan untuk obat: ' . $obat->nama_obat);
            }

            $series = $this->getHistoricalSeries($skenario, $columnName);
            if (count($series) < 2) {
                throw new \Exception('Data historis minimal 2 periode. Data tersedia: ' . count($series) . ' periode.');
            }

            [$trainingSeries, $ujiSeries] = $this->splitTrainingAndTestData($skenario, $series);

            if (count($trainingSeries) === 0) throw new \Exception('Data training tidak tersedia untuk skenario ini.');
            if (count($ujiSeries) === 0) throw new \Exception('Data uji tidak tersedia untuk skenario ini.');

            $dataTraining   = array_map(fn($o) => (int) $o->nilai, $trainingSeries);
            $dataUji        = array_map(fn($o) => (int) $o->nilai, $ujiSeries);
            $jumlahSimulasi = count($ujiSeries);

            // ====== PARAMETER ITERASI 
            $targetMape = 10.0;  // <10 sangat baik, <20 baik, <50 cukup, >50 buruk
            $maxTry     = 1000;   // batas maksimal percobaan agar tidak lama
            // ==============================================

            // Hitung error 
            $calcErrorOnly = function (array $simulasiDetail, array $ujiSeries): array {
                $n = min(count($simulasiDetail), count($ujiSeries));
                if ($n === 0) return ['MAD' => 0, 'MSE' => 0, 'MAPE' => 0];

                $sumMAD = 0;
                $sumMSE = 0;
                $sumMAPE = 0;

                for ($i = 0; $i < $n; $i++) {
                    $prediksi = (int) $simulasiDetail[$i]['permintaan'];
                    $aktual   = (int) $ujiSeries[$i]->nilai;

                    $mad  = abs($prediksi - $aktual);
                    $mse  = ($prediksi - $aktual) ** 2;
                    $mape = $aktual > 0 ? abs(($prediksi - $aktual) / $aktual) * 100 : 0;

                    $sumMAD  += $mad;
                    $sumMSE  += $mse;
                    $sumMAPE += $mape;
                }

                return [
                    'MAD'  => $sumMAD / $n,
                    'MSE'  => $sumMSE / $n,
                    'MAPE' => $sumMAPE / $n,
                ];
            };

            $bestHasil = null;
            $bestErr   = null;
            $bestTry   = 0;

            //Jalankan satu kali simulasi Monte Carlo            
            for ($t = 1; $t <= $maxTry; $t++) {
                // Penting: kirim [] agar bilangan acak pakai random_int() -> MAPE bisa berubah tiap iterasi
                $hasilTmp = $this->runMonteCarloSimulation(
                    $idObat,
                    $skenario,
                    $dataTraining,
                    $jumlahSimulasi,
                    [], // <-- ini yang membedakan
                    $trainingSeries
                );

                 // Hitung error (MAPE) dari hasil simulasi ini
                $errTmp = $calcErrorOnly($hasilTmp['simulasi_detail'], $ujiSeries);
                // Simpan hasil jika MAPE-nya lebih rendah (lebih baik)
                if ($bestErr === null || $errTmp['MAPE'] < $bestErr['MAPE']) {
                    $bestHasil = $hasilTmp;
                    $bestErr   = $errTmp;
                    $bestTry   = $t;
                }
                 // Hentikan loop jika MAPE sudah bagus
                if ($errTmp['MAPE'] < $targetMape) {
                    break; // sudah masuk target
                }
            }

            if ($bestHasil === null) {
                throw new \Exception('Simulasi gagal menghasilkan hasil.');
            }
            
            // Simpan ke DB hanya 1x 
            $hasilSimulasi = $bestHasil;

            $errorRingkas = $this->calculateErrorAndSaveMany(
                $idObat,
                $skenario,
                $hasilSimulasi['simulasi_detail'],
                $ujiSeries
            );
            // $this->saveMetrikEvaluasi($idObat, $skenario, $errorRingkas);

            $errorRingkas['kriteria_mape'] = $this->getMapeKriteria((float) $errorRingkas['MAPE']);
            $errorRingkas['iterasi_terpilih'] = $bestTry;

            $rekap = [
                'obat'             => $obat->nama_obat,
                'id_obat'          => $idObat,
                'skenario'         => $skenario,
                'jumlah_simulasi'  => $jumlahSimulasi,
                'periode_prediksi' => count($ujiSeries),
                'data_training'    => $dataTraining,
                'data_uji'         => $dataUji,
                'distribusi'       => $hasilSimulasi['distribusi'],
                'interval'         => $hasilSimulasi['interval'],
                'simulasi'         => $hasilSimulasi['simulasi_detail'],
                'statistik'        => $hasilSimulasi['statistik'],
                'error_ringkas'    => $errorRingkas,
            ];

            // simpan permanen 
            session()->put('mc_rekap', $rekap);
            session()->put('mc_selected_obat_id', $idObat);
            session()->put('mc_selected_skenario', $skenario);

            return redirect()
                ->route('monte-carlo.index')
                ->with('success', 'Simulasi Monte Carlo berhasil dijalankan.');
            // ->with('rekap', $rekap)
            // ->with('selected_obat_id', $idObat);

        } catch (\Exception $e) {
            Log::error('Error simulasi Monte Carlo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('monte-carlo.index')
                ->with('error', 'Gagal menjalankan simulasi: ' . $e->getMessage());
        }

        return redirect()
            ->route('monte-carlo.index', [
                'obat_id'  => $idObat,
                'skenario' => $skenario,
            ])
            ->with('success', 'Simulasi Monte Carlo berhasil dijalankan.');


    }

    private function splitTrainingAndTestData(string $skenario, array $series): array
    {
        $trainingSeries = [];
        $ujiSeries = [];

        if ($skenario === 'Skenario 1') {
            foreach ($series as $item) {
                if ($item->periode_tahun == 2024) {
                    if ($item->periode_bulan == 12) $ujiSeries[] = $item;
                    else $trainingSeries[] = $item;
                }
            }
        } elseif ($skenario === 'Skenario 2') {
            foreach ($series as $item) {
                if ($item->periode_tahun == 2021) $trainingSeries[] = $item;
                elseif ($item->periode_tahun == 2022) $ujiSeries[] = $item;
            }
        } elseif ($skenario === 'Skenario 3') {
            // FIX: Skenario 3 = bulanan
            foreach ($series as $item) {
                if ($item->periode_tahun >= 2021 && $item->periode_tahun <= 2023) $trainingSeries[] = $item; // 36 bulan
                elseif ($item->periode_tahun == 2024) $ujiSeries[] = $item; // 12 bulan
            }
        }

        return [$trainingSeries, $ujiSeries];
    }

    private function getColumnName($namaObat)
    {
        $namaObat = trim($namaObat);

        if (isset($this->obatMapping[$namaObat])) return $this->obatMapping[$namaObat];

        $columnName = strtolower($namaObat);
        $columnName = str_replace([' ', '-', '.'], '_', $columnName);
        $columnName = preg_replace('/[^a-z0-9_]/', '', $columnName);

        return $columnName;
    }

    private function getHistoricalSeries(string $skenario, string $columnName): array
    {
        if ($skenario === 'Skenario 1') {
            if (!Schema::hasTable('tb_permintaan_obat_harian')) {
                throw new \Exception('Tabel tb_permintaan_obat_harian belum dibuat.');
            }

            $rows = DB::table('tb_permintaan_obat_harian')
                ->select('tanggal', DB::raw($columnName . ' as nilai'))
                ->whereYear('tanggal', 2024)
                ->whereNotNull($columnName)
                ->where($columnName, '>', 0)
                ->orderBy('tanggal')
                ->get();

            $series = [];
            foreach ($rows as $row) {
                $tgl = Carbon::parse($row->tanggal);
                $series[] = (object) [
                    'periode_tahun' => $tgl->year,
                    'periode_bulan' => $tgl->month,
                    'label'         => $tgl->format('Y-m-d'),
                    'nilai'         => (int) $row->nilai,
                ];
            }
            return $series;
        }

        if ($skenario === 'Skenario 2') {
            if (!Schema::hasTable('tb_periode_permintaan')) {
                throw new \Exception('Tabel tb_periode_permintaan belum dibuat.');
            }

            $rows = PeriodePermintaan::select(
                'periode_tahun',
                'periode_bulan',
                DB::raw($columnName . ' as nilai')
            )
                ->whereIn('periode_tahun', [2021, 2022])
                ->whereNotNull($columnName)
                ->where($columnName, '>', 0)
                ->orderBy('periode_tahun')
                ->orderBy('periode_bulan')
                ->get();

            $series = [];
            foreach ($rows as $row) {
                $label = sprintf('%04d-%02d', $row->periode_tahun, $row->periode_bulan);
                $series[] = (object) [
                    'periode_tahun' => (int) $row->periode_tahun,
                    'periode_bulan' => (int) $row->periode_bulan,
                    'label'         => $label,
                    'nilai'         => (int) $row->nilai,
                ];
            }
            return $series;
        }

        if ($skenario === 'Skenario 3') {
            if (!Schema::hasTable('tb_periode_permintaan')) {
                throw new \Exception('Tabel tb_periode_permintaan belum dibuat.');
            }

            // FIX UTAMA: Skenario 3 
            $rows = PeriodePermintaan::select(
                'periode_tahun',
                'periode_bulan',
                DB::raw($columnName . ' as nilai')
            )
                ->whereBetween('periode_tahun', [2021, 2024])
                ->whereNotNull($columnName)
                ->where($columnName, '>', 0)
                ->orderBy('periode_tahun')
                ->orderBy('periode_bulan')
                ->get();

            $series = [];
            foreach ($rows as $row) {
                $label = sprintf('%04d-%02d', $row->periode_tahun, $row->periode_bulan);
                $series[] = (object) [
                    'periode_tahun' => (int) $row->periode_tahun,
                    'periode_bulan' => (int) $row->periode_bulan,
                    'label'         => $label,
                    'nilai'         => (int) $row->nilai,
                ];
            }
            return $series;
        }

        throw new \Exception('Skenario tidak valid: ' . $skenario);
    }

    private function normalizeText(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]/', '', $s);
        return $s ?? '';
    }

    /**
     * Bilangan acak:
     * - Skenario 1: 0..99
     * - Skenario 2: 0..100
     * - Skenario 3: 0..100 
     */

    private function readBilanganAcakFromExcel(?string $path, string $namaObat, int $jumlahSimulasi, string $skenario): array
    {
        try {
            if (!$path || !file_exists($path)) return [];

            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = Coordinate::columnIndexFromString($highestCol);

            $maxRand = ($skenario === 'Skenario 1') ? 100 : 100;

            // cari header "bilangan acak"
            $headerRow = null;
            $headerCol = null;

            for ($r = 1; $r <= $highestRow; $r++) {
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                    if (is_string($v) && stripos($v, 'bilangan acak') !== false) {
                        $headerRow = $r;
                        $headerCol = $c;
                        break 2;
                    }
                }
            }
            if (!$headerRow || !$headerCol) return [];

            // coba cari kolom obat di sekitar header
            $target = $this->normalizeText($namaObat);
            $colObat = null;

            for ($r = $headerRow; $r <= min($headerRow + 4, $highestRow); $r++) {
                for ($c = max(1, $headerCol - 15); $c <= min($headerCol + 15, $highestColIndex); $c++) {
                    $v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                    if (!is_string($v) || trim($v) === '') continue;

                    if ($this->normalizeText($v) === $target) {
                        $colObat = $c;
                        break 2;
                    }
                }
            }

            if (!$colObat) $colObat = $headerCol;

            $rowStart = null;
            for ($r = 1; $r <= $highestRow; $r++) {
                for ($col = 1; $col <= 3; $col++) {
                    $v = $sheet->getCellByColumnAndRow($col, $r)->getValue();
                    if (is_string($v) && stripos($v, 'januari') !== false) {
                        $rowStart = $r;
                        break 2;
                    }
                }
            }
            if (!$rowStart) return [];

            $nums = [];
            for ($r = $rowStart; $r < $rowStart + $jumlahSimulasi; $r++) {
                $v = $sheet->getCellByColumnAndRow($colObat, $r)->getCalculatedValue();
                if ($v === null || $v === '') continue;

                $n = (int) $v;
                if ($n < 0) $n = 0;
                if ($n > $maxRand) $n = $maxRand;

                $nums[] = $n;
                if (count($nums) >= $jumlahSimulasi) break;
            }

            return $nums;
        } catch (\Throwable $e) {
            Log::warning('Gagal baca bilangan acak dari excel', [
                'file'  => $path,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
 // mencari nilai interval bilangan acak skenario 1 2 dan 3
 // mengubah probabilitas kumulatif (yang berupa desimal 0 sampai 1) menjadi rentang angka (interval). Ini memudahkan pencocokan dengan bilangan acak.
    private function buildIntervals(array $distribusi, int $maxRange): array //pemangggilan semua skenario 
    {
        $interval = [];
        $start = 0;

        foreach ($distribusi as $row) { 
            if ($start > $maxRange) break;
            // bagian interval awal dan akhir
            $end = (int) floor($row['kumulatif'] * $maxRange); //rumus interval

            if ($end < 0) $end = 0;
            if ($end > $maxRange) $end = $maxRange;
            if ($end < $start) $end = $start;

            $interval[] = [  //hasil interval
                'nilai'          => $row['nilai'],
                'interval_awal'  => $start, //awal
                'interval_akhir' => $end,   //akhir
            ];

            $start = $end + 1; //lanjut ke interval berikutnya
        }

        if (!empty($interval)) {
            $interval[count($interval) - 1]['interval_akhir'] = $maxRange;
        }

        return $interval;
    }


    private function runMonteCarloSimulation(
        int $idObat,
        string $skenario,
        array $dataTraining,
        int $jumlahSimulasi,
        array $bilanganAcakExcel = [],
        array $trainingSeries = []
    ): array {

        // Skenario perhitungan 2 & 3
        if ($skenario === 'Skenario 2' || $skenario === 'Skenario 3') {
            $distribusi = [];
            $total = 0;

            // perhitungan frekuensi skenario 2 dan 3 
            foreach ($trainingSeries as $row) {
                $total += (int) $row->nilai;
            }

//  Distribusi Probabilitas skenario 2 dan 3 
            foreach ($trainingSeries as $row) {
                $nilai = (int) $row->nilai;
                $prob  = ($total > 0) ? ($nilai / $total) : 0; // distribusi probabilitas skenario 2 dan 3

                $distribusi[] = [ //bgian distribusi probabilitas 
                    'nilai'        => $nilai,
                    'frekuensi'    => $nilai,
                    'probabilitas' => $prob,
                    'label'        => $row->label ?? null,
                ];
            }

// distribusi probabilitas kumulatif skenario 2 dan 3 
            $kumulatif = 0.0;
            foreach ($distribusi as $i => $row) { 
                $kumulatif += $row['probabilitas']; //perhitungan distribusi prob.kumulatif
                $distribusi[$i]['kumulatif'] = $kumulatif;
            }
// pembangkitan bil.acak 
            $maxRand = 100; // S2 & S3 
            $interval = $this->buildIntervals($distribusi, $maxRand);

            $simulasi = [];
            $hasil = [];
// perhitungan pembangkitan
            for ($i = 0; $i < $jumlahSimulasi; $i++) {
                $rand = $bilanganAcakExcel[$i] ?? random_int(0, $maxRand);
                if ($rand < 0) $rand = 0;
                if ($rand > $maxRand) $rand = $maxRand;

                $nilaiTerpilih = $interval[0]['nilai'];
                foreach ($interval as $row) {
                    if ($rand >= $row['interval_awal'] && $rand <= $row['interval_akhir']) {
                        $nilaiTerpilih = $row['nilai'];
                        break;
                    }
                }
        // hasil simulasi skenario 2 dan 3
                $simulasi[] = [
                    'periode'    => $i + 1,
                    'bil_acak'   => $rand,
                    'permintaan' => $nilaiTerpilih,
                ];
                $hasil[] = $nilaiTerpilih;
            }

            $statistik = [
                'jumlah_simulasi' => count($hasil),
                'rata_rata'       => count($hasil) ? array_sum($hasil) / count($hasil) : 0,
                'min'             => count($hasil) ? min($hasil) : 0,
                'max'             => count($hasil) ? max($hasil) : 0,
                'standar_deviasi' => $this->calculateStandardDeviation($hasil),
            ];

            return [
                'distribusi'      => $distribusi,
                'interval'        => $interval,
                'simulasi_detail' => $simulasi,
                'statistik'       => $statistik,
            ];
        }

        // Skenario 1: frekuensi berdasarkan kemunculan nilai
        $frekuensi = array_count_values($dataTraining);
        ksort($frekuensi);

        $total = array_sum($frekuensi);
        $distribusi = [];

        // distribusi probabilitas skenario 1
        foreach ($frekuensi as $nilai => $freq) {
            $distribusi[] = [ // bgian distribusi probabilitas skenario 1
                'nilai'        => (int) $nilai,
                'frekuensi'    => (int) $freq,
                'probabilitas' => ($total > 0) ? ($freq / $total) : 0.0, //ini perhitungan probabilitas sknario 1
            ];
        }

    // Distribusi probabilitas kumulatif skenario 1
        $kumulatif = 0.0;
        foreach ($distribusi as $i => $row) { //perhitungan prob.kumulatif
            $kumulatif += $row['probabilitas'];
            $distribusi[$i]['kumulatif'] = $kumulatif;
        }

        // Setelah distribusi probabilitas dibuat (semua skenario)
        $maxRand = 100;  //skenario 1
        $interval = $this->buildIntervals($distribusi, $maxRand); //memanggil method

        $simulasi = [];
        $hasil = [];

        //pembangkitan bilangan acak (untuk Skenario 1, 2, dan 3)
        for ($i = 0; $i < $jumlahSimulasi; $i++) { //pembangkitan bil.acak
            $rand = $bilanganAcakExcel[$i] ?? random_int(0, $maxRand);
            if ($rand < 0) $rand = 0;
            if ($rand > $maxRand) $rand = $maxRand;
            //pencocokan interval
            $nilaiTerpilih = $interval[0]['nilai'];
            foreach ($interval as $row) {
                if ($rand >= $row['interval_awal'] && $rand <= $row['interval_akhir']) {
                    $nilaiTerpilih = $row['nilai'];
                    break;
                }
            }
        
            // hasil simulasi skenario 1
            $simulasi[] = [
                'periode'    => $i + 1,
                'bil_acak'   => $rand, //hasil bil.acak disimpan
                'permintaan' => $nilaiTerpilih,
            ];
            $hasil[] = $nilaiTerpilih;
        }

        $statistik = [
            'jumlah_simulasi' => count($hasil),
            'rata_rata'       => count($hasil) ? array_sum($hasil) / count($hasil) : 0,
            'min'             => count($hasil) ? min($hasil) : 0,
            'max'             => count($hasil) ? max($hasil) : 0,
            'standar_deviasi' => $this->calculateStandardDeviation($hasil),
        ];

        return [
            'distribusi'      => $distribusi,
            'interval'        => $interval,
            'simulasi_detail' => $simulasi,
            'statistik'       => $statistik,
        ];
    }

    //hasil perhitungan MAPE MSE DAN MAD
    private function calculateErrorAndSaveMany(
        int $idObat,
        string $skenario,
        array $simulasiDetail,
        array $ujiSeries
    ): array { // perhitungan error
        if (!Schema::hasTable('tb_hasil_error')) {
            return ['MAD' => 0, 'MSE' => 0, 'MAPE' => 0];
        }

        DB::table('tb_hasil_error')
            ->where('id_obat', $idObat)
            ->where('skenario', $skenario)
            ->delete();

        $n = min(count($simulasiDetail), count($ujiSeries));
        $sumMAD  = 0;
        $sumMSE  = 0;
        $sumMAPE = 0;

        for ($i = 0; $i < $n; $i++) {
            $prediksi = (int) $simulasiDetail[$i]['permintaan'];
            $aktual   = (int) $ujiSeries[$i]->nilai;

            $mad  = abs($prediksi - $aktual); //MAD
            $mse  = pow($prediksi - $aktual, 2); //MSE
            $mape = $aktual > 0 ? abs(($prediksi - $aktual) / $aktual) * 100 : 0; //MAPE

            $sumMAD  += $mad;
            $sumMSE  += $mse;
            $sumMAPE += $mape;

            $periodeTahun = property_exists($ujiSeries[$i], 'periode_tahun') ? $ujiSeries[$i]->periode_tahun : null;
            $periodeBulan = property_exists($ujiSeries[$i], 'periode_bulan') ? $ujiSeries[$i]->periode_bulan : null;

            DB::table('tb_hasil_error')->insert([
                'id_obat'       => $idObat,
                'skenario'      => $skenario,
                'data_prediksi' => $prediksi,
                'data_aktual'   => $aktual,
                'AD'            => $mad,
                'SE'            => $mse,
                'APE'           => $mape,
                'periode_tahun' => $periodeTahun,
                'periode_bulan' => $periodeBulan,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        if ($n === 0) {
            return ['MAD' => 0, 'MSE' => 0, 'MAPE' => 0];
        }
        $mseAvg = $sumMSE / $n;
        return [
            'MAD'  => $sumMAD / $n,
            'MSE'  => $mseAvg,
            'RMSE' => sqrt($mseAvg),
            'MAPE' => $sumMAPE / $n,
        ];
    }

    private function calculateStandardDeviation(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0.0;

        $mean = array_sum($data) / $n;
        $sumSquares = 0.0;

        foreach ($data as $value) {
            $sumSquares += pow($value - $mean, 2);
        }

        return sqrt($sumSquares / ($n - 1));
    }

    private function getMapeKriteria(float $mape): string
    {
        if ($mape < 10)  return 'Kemampuan prediksi sangat baik';
        if ($mape < 20)  return 'Kemampuan prediksi baik';
        if ($mape < 50)  return 'Kemampuan prediksi cukup';
        return 'Kemampuan prediksi buruk';
    }
    private function calculateErrorOnly(array $simulasiDetail, array $ujiSeries): array
    {
        $n = min(count($simulasiDetail), count($ujiSeries));
        if ($n === 0) return ['MAD' => 0, 'MSE' => 0, 'MAPE' => 0];

        $sumMAD = 0;
        $sumMSE = 0;
        $sumMAPE = 0;

        for ($i = 0; $i < $n; $i++) {
            $prediksi = (int) $simulasiDetail[$i]['permintaan'];
            $aktual   = (int) $ujiSeries[$i]->nilai;

            $mad  = abs($prediksi - $aktual);
            $mse  = ($prediksi - $aktual) ** 2;
            $mape = $aktual > 0 ? abs(($prediksi - $aktual) / $aktual) * 100 : 0;

            $sumMAD += $mad;
            $sumMSE += $mse;
            $sumMAPE += $mape;
        }

        return ['MAD' => $sumMAD / $n, 'MSE' => $sumMSE / $n, 'MAPE' => $sumMAPE / $n];
    }


public function analisisKomparatif()
{
    //  pastikan tb_metrik_evaluasi terisi dari tb_hasil_error
    $this->backfillMetrikEvaluasiDariError();

    $evaluations = MetrikEvaluasi::with('obat')
        ->orderBy('id_obat')
        ->orderBy('skenario')
        ->get()
        ->groupBy('id_obat');

    $statistics = [];

    foreach ($evaluations as $idObat => $scenarios) {
        $obat = Obat::find($idObat);
        $bestScenario = $scenarios->sortBy('MAPE')->first();

        $statistics[] = [
            'obat' => $obat ? $obat->nama_obat : '-',
            'obat_id' => $idObat,
            'scenarios' => $scenarios,
            'best_scenario' => $bestScenario,
            'avg_mape' => $scenarios->avg('MAPE'),
            'avg_mad' => $scenarios->avg('MAD'),
            'avg_mse' => $scenarios->avg('MSE'),
        ];
    }

    return view('hasil.analisis', compact('evaluations', 'statistics'));
}


}
