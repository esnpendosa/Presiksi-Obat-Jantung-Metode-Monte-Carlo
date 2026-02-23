<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DataController;

class GenerateTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'template:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate template Excel untuk import data obat';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Membuat template Excel...');
        
        $controller = new DataController();
        $result = $controller->createTemplateIfNotExists();
        
        if ($result) {
            $this->info('Template berhasil dibuat di: ' . public_path('templates/template_import_obat.xlsx'));
        } else {
            $this->error('Gagal membuat template');
        }
    }
}