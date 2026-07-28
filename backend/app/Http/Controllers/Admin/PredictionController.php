<?php

namespace App\Http\Controllers\Admin;

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

    public function index()
    {
        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'Ensemble')
            ->orderBy('updated_at', 'desc')
            ->get();

        $uniquePredictions = $predictions->unique('farm_record_id');

        return view('admin.predictions.index', compact('uniquePredictions'));
    }

    public function create()
    {
        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])->get();
        $weather = $this->weatherService->getWeather();
        return view('admin.predictions.create', compact('farmRecords', 'weather'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'farm_record_id' => 'required|exists:farm_records,id',
        ]);

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($request->farm_record_id);

        $existingPredictions = Prediction::where('farm_record_id', $farmRecord->id)->count();

        if ($existingPredictions > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This farm record already has predictions. Use the "Regenerate" option to update them.'
                ], 400);
            }
            return redirect()->route('admin.predictions.index')
                ->with('warning', 'This farm record already has predictions. Use the "Regenerate" option to update them.');
        }

        $weather = $this->weatherService->getWeather();

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
                Prediction::create([
                    'farm_record_id' => $farmRecord->id,
                    'model_type' => $model,
                    'predicted_yield_tons_ha' => $result[$model] ?? 0,
                    'input_features' => json_encode([
                        'input' => $input,
                        'weather' => $weather
                    ]),
                ]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Prediction generated successfully!'
                ]);
            }

            return redirect()->route('admin.predictions.index')
                ->with('success', 'Prediction generated successfully!');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate prediction: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to generate prediction: ' . $e->getMessage());
        }
    }

    /**
     * Update/Regenerate predictions for an existing farm record.
     * Always updates the updated_at timestamp.
     */
    public function update(Request $request, $id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

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
                // Force update using raw DB to guarantee updated_at changes
                $updated = DB::table('predictions')
                    ->where('farm_record_id', $farmRecord->id)
                    ->where('model_type', $model)
                    ->update([
                        'predicted_yield_tons_ha' => $result[$model] ?? 0,
                        'input_features' => json_encode([
                            'input' => $input,
                            'weather' => $weather
                        ]),
                        'updated_at' => now(),
                    ]);

                // If no record existed, create one
                if ($updated === 0) {
                    DB::table('predictions')->insert([
                        'farm_record_id' => $farmRecord->id,
                        'model_type' => $model,
                        'predicted_yield_tons_ha' => $result[$model] ?? 0,
                        'input_features' => json_encode([
                            'input' => $input,
                            'weather' => $weather
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Single success flash
            return redirect()->route('admin.predictions.index')
                ->with('success', 'Predictions updated successfully! (Weather: ' . $weather['temperature'] . '°C, ' . $weather['description'] . ')');

        } catch (\Exception $e) {
            // Single error flash
            return redirect()->back()
                ->with('error', 'Failed to update predictions: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $predictions = Prediction::where('farm_record_id', $id)->get();
        $rf = $predictions->where('model_type', 'RandomForest')->first();
        $xgb = $predictions->where('model_type', 'XGBoost')->first();
        $ensemble = $predictions->where('model_type', 'Ensemble')->first();
        $weather = $ensemble ? json_decode($ensemble->input_features, true)['weather'] ?? null : null;

        return view('admin.predictions.show', compact('farmRecord', 'rf', 'xgb', 'ensemble', 'weather', 'id'));
    }
}