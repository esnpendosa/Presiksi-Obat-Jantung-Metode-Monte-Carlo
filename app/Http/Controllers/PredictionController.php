<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function realtimePrediction()
    {
        // Untuk API atau prediksi real-time
        return response()->json(['status' => 'in_development']);
    }
}