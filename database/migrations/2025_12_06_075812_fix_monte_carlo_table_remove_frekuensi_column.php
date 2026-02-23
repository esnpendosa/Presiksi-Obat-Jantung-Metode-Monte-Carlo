<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixMonteCarloTableRemoveFrekuensiColumn extends Migration
{
    public function up()
    {
        // Jika ada kolom 'frekuensi', hapus
        if (Schema::hasColumn('tb_monte_carlo', 'frekuensi')) {
            Schema::table('tb_monte_carlo', function (Blueprint $table) {
                $table->dropColumn('frekuensi');
            });
        }
        
        // Pastikan kolom 'pembangkitan_acak' ada
        if (!Schema::hasColumn('tb_monte_carlo', 'pembangkitan_acak')) {
            Schema::table('tb_monte_carlo', function (Blueprint $table) {
                $table->decimal('pembangkitan_acak', 10, 4)->nullable()->after('distribusi_kumulatif');
            });
        }
    }

    public function down()
    {
        Schema::table('tb_monte_carlo', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_monte_carlo', 'frekuensi')) {
                $table->integer('frekuensi')->nullable()->after('nilai_frekuensi');
            }
            
            if (Schema::hasColumn('tb_monte_carlo', 'pembangkitan_acak')) {
                $table->dropColumn('pembangkitan_acak');
            }
        });
    }
}