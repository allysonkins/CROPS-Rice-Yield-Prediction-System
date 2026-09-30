<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

    public function predict(Request $request)
    {
        $data = $request->validate([
            'variety'                  => 'required|string',
            'variety_maturity_days'    => 'required|integer',
            'variety_max_yield'        => 'required|numeric',
            'soil_type'                => 'required|string',
            'season'                   => 'required|string',
            'seeding_method'           => 'required|string',
            'land_area_ha'             => 'required|numeric',
            'fertilizer_kg_ha'         => 'required|numeric',
            'temperature_avg'          => 'required|numeric',
            'rainfall_mm'              => 'required|numeric',
            'humidity_avg'             => 'required|numeric',
            'historical_yield_tons_ha' => 'required|numeric',
        ]);

        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:5000/predict', $data);

            if ($response->successful()) {
                $r = $response->json();
                if (isset($r['Predicted_Yield'])) {
                    return response()->json([
                        'success'         => true,
                        'Predicted_Yield' => $r['Predicted_Yield'],
                        'Confidence'      => $r['Confidence']  ?? null,
                        'Yield_Lower'     => $r['Yield_Lower'] ?? null,
                        'Yield_Upper'     => $r['Yield_Upper'] ?? null,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('ML service unavailable, using fallback yield formula.', ['error' => $e->getMessage()]);
        }

        $fallbackYield = $this->calculateFallbackYield($data);
        return response()->json([
            'success'         => true,
            'Predicted_Yield' => round($fallbackYield, 2),
            'Confidence'      => null,
            'Yield_Lower'     => null,
            'Yield_Upper'     => null,
            'fallback'        => true,
            'message'         => 'ML service unavailable — using fallback estimation.',
        ]);
    }

    protected function calculateFallbackYield($input)
    {
        $yield = 3.5;

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

        $fert = $input['fertilizer_kg_ha'];
        $yield += min(($fert / 120) * 0.6, 1.0);

        $yield -= abs($input['temperature_avg'] - 27) * 0.08;

        $rainDiff = abs($input['rainfall_mm'] - 200);
        $yield -= min(($rainDiff / 200) * 0.4, 0.6);

        if ($input['seeding_method'] === 'Transplanted') {
            $yield += 0.2;
        }

        $yield += ($input['historical_yield_tons_ha'] - 3.5) * 0.15;
        $yield += (rand(-5, 5) / 100);

        return max(2.0, min(7.5, $yield));
    }

    public function index()
    {
        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($predictions);
    }
}