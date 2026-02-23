<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Obat;
use App\Models\PeriodePermintaan;
use App\Models\MetrikEvaluasi;
use App\Models\MonteCarlo;
use App\Models\HasilError;
use App\Models\LogProses;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $obats = Obat::all();
        $totalData = [];
        
        foreach ($obats as $obat) {
            $columnName = $this->getColumnName($obat->nama_obat);
            $total = PeriodePermintaan::sum($columnName);
            $totalData[] = [
                'nama_obat' => $obat->nama_obat,
                'total' => $total
            ];
        }
        
        // Data untuk pie chart
        $pieChartData = [];
        foreach ($obats as $obat) {
            $columnName = $this->getColumnName($obat->nama_obat);
            $total = PeriodePermintaan::sum($columnName);
            $pieChartData[] = [
                'name' => $obat->nama_obat,
                'y' => $total
            ];
        }
        
        // Tambahkan data statistik lainnya
        $totalPrediksi = MonteCarlo::distinct('id_obat', 'skenario')->count();
        $totalError = HasilError::count();
        $logAktivitas = LogProses::orderBy('created_at', 'desc')->limit(5)->get();
        
        return view('dashboard', compact('totalData', 'pieChartData', 'obats', 'totalPrediksi', 'totalError', 'logAktivitas'));
    }
    
    private function getColumnName($namaObat)
    {
        $columnMap = [
            'Clopidogrel 75 Mg' => 'clopidogrel_75_mg',
            'Candesartan 8 Mg' => 'candesartan_8_mg',
            'Isosorbid Dinitrate 5 Mg' => 'isosorbid_dinitrate_5_mg',
            'Nitrokaf Retard 2.5 Mg' => 'nitrokaf_retard_25_mg'
        ];
        
        return $columnMap[$namaObat] ?? '';
    }
}