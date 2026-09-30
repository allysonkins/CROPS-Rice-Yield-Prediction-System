<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\Farm;
use App\Services\PredictionService;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected $weatherService;
    protected $predictionService;

    public function __construct(WeatherService $weatherService, PredictionService $predictionService)
    {
        $this->weatherService = $weatherService;
        $this->predictionService = $predictionService;
    }

    public function index()
    {
        $user = auth()->user();

        $query = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'XGBoost')
            ->whereHas('farmRecord', fn($q) => $q->where('status', 'Vegetative'));

        if ($user->role === 'farmer') {
            $farmIds = Farm::where('user_id', $user->id)->pluck('id');
            $farmRecordIds = FarmRecord::whereIn('farm_id', $farmIds)->pluck('id');
            $query->whereIn('farm_record_id', $farmRecordIds);
        }

        $predictions = $query->orderBy('created_at', 'desc')->get();
        $uniquePredictions = $predictions->unique('farm_record_id');

        $pendingFarmRecords = FarmRecord::where('status', 'Vegetative')
            ->whereDoesntHave('predictions', fn($q) => $q->where('model_type', 'XGBoost'))
            ->count();

        return view('admin.predictions.index', compact('uniquePredictions', 'pendingFarmRecords'));
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
        $request->validate(['farm_record_id' => 'required|exists:farm_records,id']);
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($request->farm_record_id);

        if ($farmRecord->status !== 'Vegetative') {
            return $this->errorResponse($request, 'Cannot generate a prediction for a harvested farm record.');
        }

        $existing = Prediction::where('farm_record_id', $farmRecord->id)
            ->where('model_type', 'XGBoost')->first();

        if ($existing) {
            return $this->errorResponse($request, 'This farm record already has a prediction. Use "Regenerate All" for weather refresh.');
        }

        return $this->respondTo($request, $this->predictionService->predict($farmRecord), 'created');
    }

    public function generateAll(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])
            ->where('status', 'Vegetative')
            ->whereDoesntHave('predictions', fn($q) => $q->where('model_type', 'XGBoost'))
            ->get();

        if ($farmRecords->isEmpty()) {
            return response()->json([
                'success'   => true,
                'generated' => 0,
                'failed'    => 0,
                'message'   => 'No pending farm records to predict.',
            ]);
        }

        $generated = 0; $failed = 0; $errors = [];

        foreach ($farmRecords as $record) {
            $result = $this->predictionService->predict($record);
            if ($result['success']) {
                $generated++;
            } else {
                $failed++;
                $errors[] = ($record->farm->name ?? 'Farm #' . $record->farm_id) . ': ' . $result['error'];
            }
        }

        log_activity('prediction', 'Bulk prediction generated', null, [
            'generated' => $generated,
            'failed'    => $failed,
        ]);

        return response()->json([
            'success'   => true,
            'generated' => $generated,
            'failed'    => $failed,
            'errors'    => $errors,
            'message'   => "Generated {$generated} prediction(s)" . ($failed > 0 ? ", {$failed} failed" : ''),
        ]);
    }

    public function regenerateAll(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $farmRecordIds = Prediction::where('model_type', 'XGBoost')
            ->pluck('farm_record_id')
            ->unique();

        $farmRecords = FarmRecord::with(['farm', 'riceVariety'])
            ->whereIn('id', $farmRecordIds)
            ->where('status', 'Vegetative')
            ->get();

        if ($farmRecords->isEmpty()) {
            return response()->json([
                'success'     => true,
                'regenerated' => 0,
                'failed'      => 0,
                'message'     => 'No farm records available to regenerate.',
            ]);
        }

        $regenerated = 0; $failed = 0; $errors = [];

        foreach ($farmRecords as $record) {
            $result = $this->predictionService->predict($record);
            if ($result['success']) {
                $regenerated++;
            } else {
                $failed++;
                $errors[] = ($record->farm->name ?? 'Farm #' . $record->farm_id) . ': ' . $result['error'];
            }
        }

        log_activity('prediction', 'Bulk prediction regenerated', null, [
            'regenerated' => $regenerated,
            'failed'      => $failed,
        ]);

        return response()->json([
            'success'     => true,
            'regenerated' => $regenerated,
            'failed'      => $failed,
            'errors'      => $errors,
            'message'     => "Regenerated {$regenerated} prediction(s)" . ($failed > 0 ? ", {$failed} failed" : ''),
        ]);
    }

    protected function respondTo(Request $request, array $result, string $action)
    {
        if ($result['success']) {
            log_activity('prediction', "Prediction {$action}", $result['prediction']->farmRecord, [
                'yield'      => $result['yield'],
                'confidence' => $result['confidence'] ?? null,
            ]);

            $message = "Prediction {$action}! Estimated yield: "
                     . number_format($result['yield'], 2) . ' t/ha';

            if (!empty($result['confidence'])) {
                $message .= ' (' . number_format($result['confidence'] * 100, 1) . '% confidence)';
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('admin.predictions.index')->with('success', $message);
        }

        $error = 'Failed: ' . $result['error'];
        if ($request->ajax()) {
            return response()->json(['success' => false, 'error' => $error], 500);
        }
        return redirect()->back()->with('error', $error);
    }

    protected function errorResponse(Request $request, string $error)
    {
        if ($request->ajax()) return response()->json(['success' => false, 'error' => $error], 400);
        return redirect()->route('admin.predictions.index')->with('error', $error);
    }

    public function show($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);
        $prediction = Prediction::where('farm_record_id', $id)
            ->where('model_type', 'XGBoost')->latest()->first();
        $weather = $prediction ? json_decode($prediction->input_features, true)['weather'] ?? null : null;
        return view('admin.predictions.show', compact('farmRecord', 'prediction', 'weather', 'id'));
    }

    public function history($id)
    {
        $farmRecord = FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

        $predictions = Prediction::where('farm_record_id', $id)
            ->where('model_type', 'XGBoost')
            ->orderBy('created_at', 'asc')
            ->get();

        $maxYield = $farmRecord->riceVariety
            ? $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method)
            : null;

        $analysis = null;
        if ($predictions->count() > 0) {
            $yields = $predictions->pluck('predicted_yield_tons_ha')->toArray();
            $count  = count($yields);
            $latest = end($yields);
            $first  = reset($yields);

            $direction = 'stable';
            if ($count >= 2) {
                if ($latest > $first + 0.05) $direction = 'up';
                elseif ($latest < $first - 0.05) $direction = 'down';
            }

            $analysis = [
                'count'     => $count,
                'avg'       => array_sum($yields) / $count,
                'best'      => max($yields),
                'worst'     => min($yields),
                'first'     => $first,
                'latest'    => $latest,
                'delta'     => $latest - $first,
                'delta_pct' => $first > 0 ? (($latest - $first) / $first) * 100 : 0,
                'direction' => $direction,
                'maxYield'  => $maxYield,
            ];
        }

        $predictions = $predictions->reverse()->values();

        return view('admin.predictions.history-page', compact('farmRecord', 'predictions', 'analysis'));
    }

    public function destroy($id)
    {
        if (auth()->user()->role === 'farmer') {
            return redirect()->route('admin.predictions.index')->with('error', 'Farmers cannot delete predictions.');
        }

        try {
            $prediction = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])->findOrFail($id);

            log_activity('deleted', 'Prediction deleted', $prediction->farmRecord, [
                'prediction_id' => $prediction->id,
                'farm'          => $prediction->farmRecord->farm->name ?? 'N/A',
                'variety'       => $prediction->farmRecord->riceVariety->name ?? 'N/A',
            ]);

            $prediction->delete();

            return redirect()->route('admin.predictions.index')->with('success', 'Prediction deleted.');

        } catch (\Exception $e) {
            return redirect()->route('admin.predictions.index')
                ->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}