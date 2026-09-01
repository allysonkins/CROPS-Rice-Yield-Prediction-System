<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Public API endpoint for the Yield Simulator (AJAX calls from frontend)
     */
    public function predict(Request $request)
    {
        $request->validate([
            'variety' => 'required|string',
            'soil_type' => 'required|string',
            'season' => 'required|string',
            'seeding_method' => 'required|string',
            'fertilizer_kg_ha' => 'required|numeric',
            'temperature_avg' => 'required|numeric',
            'rainfall_mm' => 'required|numeric',
            'humidity_avg' => 'required|numeric',
            'historical_yield_tons_ha' => 'required|numeric',
        ]);

        // Prepare input for ML service
        $input = [
            'barangay' => 'Calaocan', // Default for simulator
            'variety' => $request->variety,
            'soil_type' => $request->soil_type,
            'season' => $request->season,
            'seeding_method' => $request->seeding_method,
            'fertilizer_kg_ha' => (float) $request->fertilizer_kg_ha,
            'temperature_avg' => (float) $request->temperature_avg,
            'rainfall_mm' => (float) $request->rainfall_mm,
            'humidity_avg' => (float) $request->humidity_avg,
            'historical_yield_tons_ha' => (float) $request->historical_yield_tons_ha,
        ];

        try {
            $response = Http::post('http://127.0.0.1:5000/predict', $input);
            $result = $response->json();

            // The ML service returns RandomForest, XGBoost, Ensemble.
            // We only use RandomForest now.
            $yield = $result['RandomForest'] ?? $result['Ensemble'] ?? null;

            if ($yield !== null) {
                return response()->json([
                    'Predicted_Yield' => round($yield, 2),
                    'success' => true,
                ]);
            } else {
                return response()->json([
                    'error' => 'Invalid response from ML service.',
                    'success' => false,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'ML service unavailable: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    /**
     * Get all predictions (for API)
     */
    public function index()
    {
        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($predictions);
    }
}