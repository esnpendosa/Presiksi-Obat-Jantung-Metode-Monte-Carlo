<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Obat; // Tambahkan ini

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tambahkan kode ini untuk membuat variabel $obats tersedia di layouts/app.blade.php
        View::composer('layouts.app', function ($view) {
            $view->with('obats', Obat::all());
        });
    }
}