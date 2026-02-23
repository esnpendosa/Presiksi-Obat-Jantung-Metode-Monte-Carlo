<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PeriodePermintaanSeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama jika ada
        DB::table('tb_periode_permintaan')->truncate();
        
        $data = [];
        $startDate = Carbon::create(2021, 1, 1);
        
        // Generate data 3 tahun (2021-2023)
        for ($i = 0; $i < 36; $i++) {
            $currentDate = $startDate->copy()->addMonths($i);
            
            $data[] = [
                'periode_tahun' => $currentDate->year,
                'periode_bulan' => $currentDate->month,
                'clopidogrel_75_mg' => rand(100, 500),
                'candesartan_8_mg' => rand(80, 400),
                'isosorbid_dinitrate_5_mg' => rand(60, 300),
                'nitrokaf_retard_25_mg' => rand(40, 200),
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];
        }
        
        DB::table('tb_periode_permintaan')->insert($data);
        
        $this->command->info('Data dummy periode permintaan berhasil ditambahkan!');
        $this->command->info('Total data: ' . count($data) . ' bulan');
    }
}