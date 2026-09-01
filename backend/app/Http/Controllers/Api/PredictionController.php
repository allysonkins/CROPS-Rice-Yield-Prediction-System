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
            // Try to call ML service with timeout (5 seconds)
            $response = Http::timeout(5)->post('http://127.0.0.1:5000/predict', $input);

            if ($response->successful()) {
                $result = $response->json();
                // The ML service returns: { "Predicted_Yield": 4.73, "Model": "Random Forest" }
                $yield = $result['Predicted_Yield'] ?? $result['RandomForest'] ?? null;

                if ($yield !== null) {
                    return response()->json([
                        'Predicted_Yield' => round($yield, 2),
                        'success' => true,
                    ]);
                }
            }
        } catch (\Exception $e) {
            // ML service unavailable – log and fallback
            \Log::warning('ML service unavailable, using fallback yield formula.', ['error' => $e->getMessage()]);
        }

        // Fallback: calculate a simple yield based on inputs
        $fallbackYield = $this->calculateFallbackYield($input);
        return response()->json([
            'Predicted_Yield' => round($fallbackYield, 2),
            'success' => true,
            'fallback' => true,
            'message' => 'ML service unavailable – using fallback estimation.',
        ]);
    }

    /**
     * Simple fallback yield formula (if ML service is down).
     */
    protected function calculateFallbackYield($input)
    {
        // Base yield
        $yield = 3.5;

        // Variety bonus (mapped from known varieties)
        $varietyBonuses = [
            'NSIC Rc 222' => 1.5,
            'NSIC Rc 160' => 0.3,
            'Mestiso 20' => 0.8,
            'NSIC Rc 216' => 0.6,
            'NSIC Rc 480' => 0.4,
            'NSIC Rc 512 (Tubigan 44)' => 0.7,
            'NSIC RC 402 (Tubigan 36)' => 0.5,
            'NSIC Rc 534 (Salinas 29)' => 0.4,
            'NSIC 2016 Rc 456H (Mestiso 78)' => 1.2,
            'NSIC Rc234H (MESTISO 27)' => 1.0,
            'NSIC Rc 486 (Mestiso 80)' => 1.1,
            'Angelica (NSIC Rc122)' => 0.9,
            'NSIC Rc216 (Tubigan 17)' => 0.6,
            'Dinorado' => 0.6,
            'IR64' => 0.2,
        ];
        $yield += $varietyBonuses[$input['variety']] ?? 0.2;

        // Fertilizer effect (diminishing returns)
        $fert = $input['fertilizer_kg_ha'];
        $fertEffect = min(($fert / 120) * 0.6, 1.0);
        $yield += $fertEffect;

        // Temperature effect (optimum ~27°C)
        $tempDiff = abs($input['temperature_avg'] - 27);
        $yield -= $tempDiff * 0.08;

        // Rainfall effect (optimum ~200mm)
        $rainDiff = abs($input['rainfall_mm'] - 200);
        $rainPenalty = ($rainDiff / 200) * 0.4;
        $yield -= min($rainPenalty, 0.6);

        // Seeding method boost
        if ($input['seeding_method'] === 'Transplanted') {
            $yield += 0.2;
        }

        // Historical yield influence (if available)
        $yield += ($input['historical_yield_tons_ha'] - 3.5) * 0.15;

        // Random noise (small variation to simulate real data)
        $yield += (rand(-5, 5) / 100);

        // Clamp between 2.0 and 7.5 t/ha
        return max(2.0, min(7.5, $yield));
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