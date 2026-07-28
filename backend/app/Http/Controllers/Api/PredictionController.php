<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PredictionController extends Controller
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function predict(Request $request)
    {
        // Remove or comment out the auth check for testing
        // if (!auth()->check()) {
        //     return response()->json([
        //         'success' => false,
        //         'error' => 'Unauthorized. Please log in first.'
        //     ], 401);
        // }

        $request->validate([
            'farm_record_id' => 'required|exists:farm_records,id',
        ]);

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($request->farm_record_id);

        // Fetch real-time weather data
        $weather = $this->weatherService->getWeather();

        // Prepare input for ML service
        $input = [
            'barangay' => $farmRecord->farm->barangay,
            'variety' => $farmRecord->riceVariety->name,
            'soil_type' => $farmRecord->farm->soil_type,
            'season' => $farmRecord->season,
            'seeding_method' => $farmRecord->seeding_method ?? 'Transplanted',
            'fertilizer_kg_ha' => (float) $farmRecord->fertilizer_kg_ha,
            'temperature_avg' => $weather['temperature'],
            'rainfall_mm' => $weather['rainfall'],
            'humidity_avg' => $weather['humidity'],
            'historical_yield_tons_ha' => (float) ($farmRecord->historical_yield_tons_ha ?? 3.5),
        ];

        try {
            $response = Http::post('http://127.0.0.1:5000/predict', $input);
            $result = $response->json();

            $models = ['RandomForest', 'XGBoost', 'Ensemble'];
            foreach ($models as $model) {
                // ✅ UPDATE OR CREATE - This will update existing predictions instead of duplicating
                DB::table('predictions')
                    ->updateOrInsert(
                        [
                            'farm_record_id' => $farmRecord->id,
                            'model_type' => $model,
                        ],
                        [
                            'predicted_yield_tons_ha' => $result[$model] ?? 0,
                            'input_features' => json_encode([
                                'input' => $input,
                                'weather' => $weather
                            ]),
                            'updated_at' => now(), // ✅ ALWAYS updates the timestamp
                            'created_at' => DB::raw('created_at'), // Keep original created_at
                        ]
                    );
            }

            // Get the updated predictions to return
            $updatedPredictions = Prediction::where('farm_record_id', $farmRecord->id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Prediction generated successfully!',
                'data' => $result,
                'weather' => $weather,
                'predictions' => $updatedPredictions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'ML service unavailable: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Get only the latest Ensemble prediction per farm record
        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'Ensemble')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->unique('farm_record_id');

        return response()->json($predictions);
    }
}