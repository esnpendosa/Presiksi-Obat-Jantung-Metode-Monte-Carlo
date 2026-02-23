<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        DB::table('tb_periode_permintaan')->truncate();
        DB::table('tb_monte_carlo')->truncate();
        DB::table('tb_hasil_error')->truncate();
        DB::table('tb_metrik_evaluasi')->truncate();
        DB::table('tb_log_proses')->truncate();
        DB::table('tb_hasil_simulasi')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Insert data periode
        $data = [];
        for ($year = 2021; $year <= 2023; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $data[] = [
                    'periode_tahun' => $year,
                    'periode_bulan' => $month,
                    'clopidogrel_75_mg' => rand(100, 500),
                    'candesartan_8_mg' => rand(80, 400),
                    'isosorbid_dinitrate_5_mg' => rand(60, 300),
                    'nitrokaf_retard_25_mg' => rand(40, 200),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        DB::table('tb_periode_permintaan')->insert($data);
        
        $this->command->info('Database seeded successfully!');
        $this->command->info('Data periode: ' . count($data) . ' records');
    }
}