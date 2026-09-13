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

        // Get latest prediction per farm record
        $predictions = $query->orderBy('created_at', 'desc')->get();
        $uniquePredictions = $predictions->unique('farm_record_id');

        $barangayData = [];
        $barangays = $predictions->groupBy(function ($p) {
            return $p->farmRecord->farm->barangay ?? 'Unknown';
        });

        foreach ($barangays as $barangay => $items) {
            $avgYield = $items->avg('predicted_yield_tons_ha');
            $barangayData[] = [
                'barangay' => $barangay,
                'predicted_yield' => number_format($avgYield, 2),
                'confidence' => round(85 + rand(-5, 10)),
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
        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])
            ->where('status', 'Vegetative')
            ->get();
        $weather = $this->weatherService->getWeather();
        return view('admin.predictions.create', compact('farmRecords', 'weather'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'farm_record_id' => 'required|exists:farm_records,id',
        ]);

        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($request->farm_record_id);

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
                    'error' => 'This farm record already has a Random Forest prediction. Use "Regenerate" to add a new one.'
                ], 400);
            }
            return redirect()->route('admin.predictions.index')
                ->with('warning', 'This farm record already has a prediction. Use "Regenerate" to add a new one.');
        }

        return $this->runPrediction($farmRecord, $request, 'created');
    }

    /**
     * Regenerate — always creates a NEW prediction (history kept).
     */
    public function update(Request $request, $id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

        if ($farmRecord->status !== 'Vegetative') {
            return redirect()->back()
                ->with('error', 'Cannot regenerate a prediction for a harvested farm record.');
        }

        return $this->runPrediction($farmRecord, $request, 'regenerated');
    }

    /**
     * Shared prediction logic for store() and update().
     */
    protected function runPrediction(FarmRecord $farmRecord, Request $request, $action)
    {
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

            // Cap using variety's max for the seeding method
            $maxYield = $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method);
            if ($maxYield !== null && $yield > $maxYield) {
                $yield = $maxYield;
            }

            // Always create a new Prediction (history is kept)
            $prediction = Prediction::create([
                'farm_record_id' => $farmRecord->id,
                'model_type' => 'RandomForest',
                'predicted_yield_tons_ha' => $yield,
                'input_features' => json_encode([
                    'input' => $input,
                    'weather' => $weather,
                ]),
            ]);

            log_activity('prediction', "Prediction {$action}", $farmRecord, [
                'farm' => $farmRecord->farm->name,
                'variety' => $farmRecord->riceVariety->name,
                'yield' => $yield,
                'prediction_id' => $prediction->id,
            ]);

            $message = $action === 'created'
                ? 'Prediction generated successfully!'
                : 'Prediction regenerated successfully! (Weather: ' . $weather['temperature'] . '°C, ' . $weather['description'] . ')';

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('admin.predictions.index')->with('success', $message);

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

    public function show($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $prediction = Prediction::where('farm_record_id', $id)
            ->where('model_type', 'RandomForest')
            ->latest('created_at')
            ->first();

        $weather = $prediction ? json_decode($prediction->input_features, true)['weather'] ?? null : null;

        return view('admin.predictions.show', compact('farmRecord', 'prediction', 'weather', 'id'));
    }

    /**
     * Show the full prediction history with trend analysis for a farm record.
     */
    public function history($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

        // Get in chronological order (oldest first) for trend analysis
        $predictions = Prediction::where('farm_record_id', $id)
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'asc')
            ->get();

        // --- Analysis ---
        $maxYield = $farmRecord->riceVariety
            ? $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method)
            : null;

        $analysis = null;
        if ($predictions->count() > 0) {
            $yields = $predictions->pluck('predicted_yield_tons_ha')->toArray();
            $count = count($yields);

            $latest = end($yields);
            $first  = reset($yields);
            $best   = max($yields);
            $worst  = min($yields);
            $avg    = array_sum($yields) / $count;

            // Trend direction — compare latest vs first
            $direction = 'stable';
            if ($count >= 2) {
                if ($latest > $first + 0.05) {
                    $direction = 'up';
                } elseif ($latest < $first - 0.05) {
                    $direction = 'down';
                }
            }

            // Status distribution (using variety-specific max)
            $statusCounts = ['high' => 0, 'medium' => 0, 'low' => 0];
            foreach ($predictions as $p) {
                $y = $p->predicted_yield_tons_ha;
                if ($maxYield !== null && $maxYield > 0) {
                    $ratio = $y / $maxYield;
                    $key = $ratio >= 0.9 ? 'high' : ($ratio >= 0.7 ? 'medium' : 'low');
                } else {
                    $key = $y >= 4.5 ? 'high' : ($y >= 3.5 ? 'medium' : 'low');
                }
                $statusCounts[$key]++;
            }

            $analysis = [
                'count'        => $count,
                'avg'          => $avg,
                'best'         => $best,
                'worst'        => $worst,
                'first'        => $first,
                'latest'       => $latest,
                'delta'        => $latest - $first,
                'delta_pct'    => $first > 0 ? (($latest - $first) / $first) * 100 : 0,
                'direction'    => $direction,
                'statusCounts' => $statusCounts,
                'maxYield'     => $maxYield,
            ];
        }

        // Reverse for display (newest first)
        $predictions = $predictions->reverse()->values();

        return view('admin.predictions.history', compact('farmRecord', 'predictions', 'analysis'));
    }
}