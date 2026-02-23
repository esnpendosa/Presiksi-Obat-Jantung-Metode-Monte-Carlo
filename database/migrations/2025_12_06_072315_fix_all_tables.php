<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Create tb_obat
        if (!Schema::hasTable('tb_obat')) {
            Schema::create('tb_obat', function (Blueprint $table) {
                $table->unsignedInteger('id_obat')->autoIncrement(); // PASTIKAN UNSIGNED INTEGER
                $table->string('nama_obat', 100);
                $table->string('jenis_obat', 50)->nullable();
                $table->string('satuan', 20)->default('Tablet');
                $table->timestamps();
            });
            
            // Insert data obat
            DB::table('tb_obat')->insert([
                ['nama_obat' => 'Clopidogrel 75 Mg', 'jenis_obat' => 'Tablet', 'satuan' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
                ['nama_obat' => 'Candesartan 8 Mg', 'jenis_obat' => 'Tablet', 'satuan' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
                ['nama_obat' => 'Isosorbid Dinitrate 5 Mg', 'jenis_obat' => 'Tablet', 'satuan' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
                ['nama_obat' => 'Nitrokaf Retard 2.5 Mg', 'jenis_obat' => 'Tablet', 'satuan' => 'Tablet', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        
        // 2. Create tb_periode_permintaan
        if (!Schema::hasTable('tb_periode_permintaan')) {
            Schema::create('tb_periode_permintaan', function (Blueprint $table) {
                $table->id('id_periode');
                $table->year('periode_tahun');
                $table->unsignedTinyInteger('periode_bulan');
                $table->unsignedInteger('clopidogrel_75_mg')->default(0);
                $table->unsignedInteger('candesartan_8_mg')->default(0);
                $table->unsignedInteger('isosorbid_dinitrate_5_mg')->default(0);
                $table->unsignedInteger('nitrokaf_retard_25_mg')->default(0);
                $table->timestamps();
                
                $table->unique(['periode_tahun', 'periode_bulan'], 'unique_periode');
            });
            
            // Insert data dummy
            $this->insertDummyData();
        }
        
        // 3. Create tb_monte_carlo (TANPA FOREIGN KEY DULU)
        if (!Schema::hasTable('tb_monte_carlo')) {
            Schema::create('tb_monte_carlo', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_obat'); // SAMA DENGAN tb_obat.id_obat
                $table->string('skenario', 20)->default('Skenario 1');
                $table->decimal('nilai_frekuensi', 10, 2)->nullable();
                $table->decimal('distribusi_probabilitas', 10, 4)->nullable();
                $table->decimal('distribusi_kumulatif', 10, 4)->nullable();
                $table->unsignedInteger('interval_bil_acak_awal')->nullable();
                $table->unsignedInteger('interval_bil_acak_akhir')->nullable();
                $table->decimal('pembangkitan_acak', 10, 4)->nullable();
                $table->unsignedInteger('simulasi_permintaan')->nullable();
                $table->timestamps();
                
                $table->index(['id_obat', 'skenario'], 'idx_obat_skenario');
                // JANGAN TAMBAH FOREIGN KEY DI SINI - TAMBAH NANTI
            });
        }
        
        // 4. Create tb_hasil_error (TANPA FOREIGN KEY DULU)
        if (!Schema::hasTable('tb_hasil_error')) {
            Schema::create('tb_hasil_error', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_obat'); // SAMA DENGAN tb_obat.id_obat
                $table->string('skenario', 20)->default('Skenario 1');
                $table->unsignedInteger('data_prediksi')->nullable();
                $table->unsignedInteger('data_aktual')->nullable();
                $table->decimal('AD', 10, 2)->nullable();
                $table->decimal('SE', 10, 2)->nullable();
                $table->decimal('APE', 10, 4)->nullable();
                $table->string('simulasi', 50)->nullable();
                $table->decimal('hitung_error', 10, 4)->nullable();
                $table->timestamps();
                
                $table->index(['id_obat', 'skenario'], 'idx_obat_skenario');
                // JANGAN TAMBAH FOREIGN KEY DI SINI
            });
        }
        
        // 5. Create tb_metrik_evaluasi (TANPA FOREIGN KEY DULU)
        if (!Schema::hasTable('tb_metrik_evaluasi')) {
            Schema::create('tb_metrik_evaluasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_obat'); // SAMA DENGAN tb_obat.id_obat
                $table->string('skenario', 20)->default('Skenario 1');
                $table->decimal('MAPE', 10, 4)->nullable();
                $table->decimal('MSE', 10, 4)->nullable();
                $table->decimal('MAD', 10, 4)->nullable();
                $table->string('kategori_akurasi', 20)->nullable();
                $table->timestamps();
                
                $table->unique(['id_obat', 'skenario'], 'unique_obat_skenario');
                // JANGAN TAMBAH FOREIGN KEY DI SINI
            });
        }
        
        // 6. Create tb_log_proses
        if (!Schema::hasTable('tb_log_proses')) {
            Schema::create('tb_log_proses', function (Blueprint $table) {
                $table->id('id_log');
                $table->string('proses', 100);
                $table->enum('status', ['SUCCESS', 'ERROR', 'PROCESSING'])->default('PROCESSING');
                $table->text('pesan')->nullable();
                $table->timestamps();
            });
        }
        
        // 7. Create tb_hasil_simulasi (TANPA FOREIGN KEY DULU)
        if (!Schema::hasTable('tb_hasil_simulasi')) {
            Schema::create('tb_hasil_simulasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_obat'); // SAMA DENGAN tb_obat.id_obat
                $table->string('skenario', 20);
                $table->json('statistik');
                $table->timestamps();
                // JANGAN TAMBAH FOREIGN KEY DI SINI
            });
        }
        
        // 8. TAMBAH FOREIGN KEY SETELAH SEMUA TABEL DIBUAT
        $this->addForeignKeys();
        
        // 9. Create view
        if (!DB::select("SELECT table_name FROM information_schema.views WHERE table_name = 'vw_hasil_prediksi'")) {
            DB::statement("
                CREATE VIEW vw_hasil_prediksi AS
                SELECT 
                    o.nama_obat,
                    m.skenario,
                    m.simulasi_permintaan as prediksi,
                    h.data_aktual,
                    h.AD,
                    h.SE,
                    h.APE,
                    me.MAPE,
                    me.MSE,
                    me.MAD
                FROM tb_monte_carlo m
                JOIN tb_obat o ON m.id_obat = o.id_obat
                LEFT JOIN tb_hasil_error h ON m.id_obat = h.id_obat AND m.skenario = h.skenario
                LEFT JOIN tb_metrik_evaluasi me ON m.id_obat = me.id_obat AND m.skenario = me.skenario
                GROUP BY o.nama_obat, m.skenario, m.simulasi_permintaan, h.data_aktual, h.AD, h.SE, h.APE, me.MAPE, me.MSE, me.MAD
            ");
        }
    }
    
    /**
     * Insert data dummy
     */
    private function insertDummyData()
    {
        $data = [];
        $startYear = 2021;
        
        for ($year = $startYear; $year <= 2023; $year++) {
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
    }
    
    /**
     * Tambah foreign key setelah semua tabel dibuat
     */
    private function addForeignKeys()
    {
        // Nonaktifkan foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Tambah foreign key ke tb_monte_carlo
        Schema::table('tb_monte_carlo', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_monte_carlo', 'id_obat')) {
                $table->unsignedInteger('id_obat')->after('id');
            }
            $table->foreign('id_obat')
                  ->references('id_obat')
                  ->on('tb_obat')
                  ->onDelete('cascade');
        });
        
        // Tambah foreign key ke tb_hasil_error
        Schema::table('tb_hasil_error', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_hasil_error', 'id_obat')) {
                $table->unsignedInteger('id_obat')->after('id');
            }
            $table->foreign('id_obat')
                  ->references('id_obat')
                  ->on('tb_obat')
                  ->onDelete('cascade');
        });
        
        // Tambah foreign key ke tb_metrik_evaluasi
        Schema::table('tb_metrik_evaluasi', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_metrik_evaluasi', 'id_obat')) {
                $table->unsignedInteger('id_obat')->after('id');
            }
            $table->foreign('id_obat')
                  ->references('id_obat')
                  ->on('tb_obat')
                  ->onDelete('cascade');
        });
        
        // Tambah foreign key ke tb_hasil_simulasi
        Schema::table('tb_hasil_simulasi', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_hasil_simulasi', 'id_obat')) {
                $table->unsignedInteger('id_obat')->after('id');
            }
            $table->foreign('id_obat')
                  ->references('id_obat')
                  ->on('tb_obat')
                  ->onDelete('cascade');
        });
        
        // Aktifkan foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        // Hapus view
        DB::statement("DROP VIEW IF EXISTS vw_hasil_prediksi");
        
        // Nonaktifkan foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Hapus tabel
        Schema::dropIfExists('tb_hasil_simulasi');
        Schema::dropIfExists('tb_log_proses');
        Schema::dropIfExists('tb_metrik_evaluasi');
        Schema::dropIfExists('tb_hasil_error');
        Schema::dropIfExists('tb_monte_carlo');
        Schema::dropIfExists('tb_periode_permintaan');
        Schema::dropIfExists('tb_obat');
        
        // Aktifkan foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};