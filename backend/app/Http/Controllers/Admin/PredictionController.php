<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\Farm;
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
        $user = auth()->user();

        $query = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest');

        if ($user->role === 'farmer') {
            $farmIds = Farm::where('user_id', $user->id)->pluck('id');
            $farmRecordIds = FarmRecord::whereIn('farm_id', $farmIds)->pluck('id');
            $query->whereIn('farm_record_id', $farmRecordIds);
        }

        $predictions = $query->orderBy('updated_at', 'desc')->get();
        $uniquePredictions = $predictions->unique('farm_record_id');

        // Prepare barangay-level predictions
        $barangayData = [];
        $barangays = $predictions->groupBy(function($p) {
            return $p->farmRecord->farm->barangay ?? 'Unknown';
        });

        foreach ($barangays as $barangay => $items) {
            $avgYield = $items->avg('predicted_yield_tons_ha');
            $barangayData[] = [
                'barangay' => $barangay,
                'predicted_yield' => number_format($avgYield, 2),
                'confidence' => round(85 + rand(-5, 10)), // Placeholder
                'historical_yield' => number_format($avgYield - 0.2, 2),
                'moisture' => rand(60, 90),
                'outlook' => $avgYield >= 4.5 ? 'Optimal' : 'Monitor',
                'action' => $avgYield >= 4.5 ? 'Continue current practices' : 'Consider intervention',
            ];
        }

        $cityAverage = $predictions->avg('predicted_yield_tons_ha');

        return view('admin.predictions.index', compact('uniquePredictions', 'barangayData', 'cityAverage'));
    }

    public function create()
    {
        // Only show vegetative farm records for prediction
        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])
            ->where('status', 'Vegetative')
            ->get();
        $weather = $this->weatherService->getWeather();
        return view('admin.predictions.create', compact('farmRecords', 'weather'));
    }

    /**
     * Generate a new prediction (only for Vegetative records).
     */
    public function store(Request $request)
    {
        $request->validate([
            'farm_record_id' => 'required|exists:farm_records,id',
        ]);

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($request->farm_record_id);

        // ===== STATUS CHECK =====
        if ($farmRecord->status !== 'Vegetative') {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot generate a prediction for a harvested farm record. Only vegetative (growing) records can be predicted.'
                ], 400);
            }
            return redirect()->route('admin.predictions.index')
                ->with('error', 'Cannot generate a prediction for a harvested farm record.');
        }

        $existing = Prediction::where('farm_record_id', $farmRecord->id)
            ->where('model_type', 'RandomForest')
            ->first();

        if ($existing) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'This farm record already has a Random Forest prediction. Use "Regenerate" to update it.'
                ], 400);
            }
            return redirect()->route('admin.predictions.index')
                ->with('warning', 'This farm record already has a prediction. Use "Regenerate" to update it.');
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

            $yield = $result['Predicted_Yield'] ?? $result['RandomForest'] ?? null;

            if ($yield === null) {
                throw new \Exception('ML service did not return a valid yield.');
            }

            Prediction::create([
                'farm_record_id' => $farmRecord->id,
                'model_type' => 'RandomForest',
                'predicted_yield_tons_ha' => $yield,
                'input_features' => json_encode([
                    'input' => $input,
                    'weather' => $weather
                ]),
            ]);

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
     * Regenerate an existing prediction (only for Vegetative records).
     */
    public function update(Request $request, $id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

        // ===== STATUS CHECK =====
        if ($farmRecord->status !== 'Vegetative') {
            return redirect()->back()
                ->with('error', 'Cannot regenerate a prediction for a harvested farm record.');
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

            $yield = $result['Predicted_Yield'] ?? $result['RandomForest'] ?? null;

            if ($yield === null) {
                throw new \Exception('ML service did not return a valid yield.');
            }

            Prediction::updateOrCreate(
                [
                    'farm_record_id' => $farmRecord->id,
                    'model_type' => 'RandomForest',
                ],
                [
                    'predicted_yield_tons_ha' => $yield,
                    'input_features' => json_encode([
                        'input' => $input,
                        'weather' => $weather
                    ]),
                    'updated_at' => now(),
                ]
            );

            return redirect()->route('admin.predictions.index')
                ->with('success', 'Prediction updated successfully! (Weather: ' . $weather['temperature'] . '°C, ' . $weather['description'] . ')');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update prediction: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $prediction = Prediction::where('farm_record_id', $id)
            ->where('model_type', 'RandomForest')
            ->first();

        $weather = $prediction ? json_decode($prediction->input_features, true)['weather'] ?? null : null;

        return view('admin.predictions.show', compact('farmRecord', 'prediction', 'weather', 'id'));
    }
}