<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\Farm;
use App\Models\RiceVariety;
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

    public function index(Request $request)
    {
        $user = auth()->user();

        // ── Base query — eager-load farmer + farm + variety + record ──
        $query = Prediction::query()
            ->with([
                'farmRecord.farm.user',
                'farmRecord.riceVariety',
            ])
            ->where('model_type', 'XGBoost');

        if ($user->role === 'farmer') {
            $farmIds = Farm::where('user_id', $user->id)->pluck('id');
            $farmRecordIds = FarmRecord::whereIn('farm_id', $farmIds)->pluck('id');
            $query->whereIn('farm_record_id', $farmRecordIds);
        }

        // ══════════════════════════════════════════════════════════
        // SERVER-SIDE FILTERS
        // ══════════════════════════════════════════════════════════

        // Year — accept 'all' to show every year
        $yearFilter = $request->input('year', 'all');
        if ($yearFilter !== 'all' && is_numeric($yearFilter)) {
            $query->whereHas('farmRecord', fn ($q) => $q->where('year', (int) $yearFilter));
        }

        // Date range on prediction created_at
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Farm name / id
        if ($farm = trim((string) $request->input('farm'))) {
            $query->whereHas('farmRecord.farm', function ($q) use ($farm) {
                $q->where('name', 'like', "%{$farm}%")
                  ->orWhere('id', $farm);
            });
        }

        // Variety
        if ($varietyId = $request->input('variety')) {
            $query->whereHas('farmRecord', fn ($q) => $q->where('rice_variety_id', $varietyId));
        }

                // Season — accepts 'Dry' | 'Wet' | 'Off-Season'
        if ($season = $request->input('season')) {
            $query->whereHas('farmRecord', function ($q) use ($season) {
                if ($season === 'Off-Season') {
                    $q->where(function ($q2) {
                        $q2->where('season', 'Off-Season')
                           ->orWhere('season', 'Off Season');
                    });
                } else {
                    $q->where('season', 'like', "%{$season}%");
                }
            });
        }

        // ── Farm record status (Vegetative / Harvested) ──
        if ($recordStatus = $request->input('record_status')) {
            $query->whereHas('farmRecord', fn ($q) => $q->where('status', $recordStatus));
        }

        // ── Water source (Rainfed / Irrigated / Both) ──
        if ($waterSource = $request->input('water_source')) {
            $query->whereHas('farmRecord', fn ($q) => $q->where('water_source', $waterSource));
        }

        // ── Confidence tier (high >= 0.90 / medium 0.70-0.90 / low < 0.70) ──
        if (($conf = $request->input('confidence')) && $conf !== 'all') {
            if ($conf === 'high') {
                $query->where('confidence', '>=', 0.90);
            } elseif ($conf === 'medium') {
                $query->where('confidence', '>=', 0.70)
                      ->where('confidence', '<', 0.90);
            } elseif ($conf === 'low') {
                $query->where('confidence', '<', 0.70);
            }
        }

        // Barangay
        if ($brgy = $request->input('barangay')) {
            $query->whereHas('farmRecord.farm', fn ($q) => $q->where('barangay', $brgy));
        }

        // Free-text search across everything
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('farmRecord.farm.user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('rsbsa_number', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhereHas('farmRecord.farm', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('barangay', 'like', "%{$search}%")
                       ->orWhere('id', $search);
                })
                ->orWhereHas('farmRecord.riceVariety', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('farmRecord', function ($q2) use ($search) {
                    $q2->where('season', 'like', "%{$search}%")
                       ->orWhere('year', $search);
                });
            });
        }

        // ── Sort ──
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['created_at', 'predicted_yield_tons_ha', 'confidence'];

        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';
        if (!in_array($direction, ['asc', 'desc'], true)) $direction = 'desc';

        $perPage = max(10, min((int) $request->input('per_page', 25), 100));

        // ══════════════════════════════════════════════════════════
        // STATS — computed on the FULL filtered set (deduped), not the page
        // ══════════════════════════════════════════════════════════
        $statsQuery = clone $query;
        $statsQuery->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('predictions')
                ->where('model_type', 'XGBoost')
                ->groupBy('farm_record_id');
        });

        $totalPredictions = (clone $statsQuery)->count();
        $avgCityYield     = (clone $statsQuery)->whereNotNull('predicted_yield_tons_ha')->avg('predicted_yield_tons_ha');
        $avgConfidence    = (clone $statsQuery)->whereNotNull('confidence')->avg('confidence');

        // ══════════════════════════════════════════════════════════
        // PAGINATED RESULTS
        // Deduped to latest per farm record, and Growing seasons
        // always sort above Harvested ones.
        // ══════════════════════════════════════════════════════════
        $statusPriority = "(SELECT CASE WHEN farm_records.status = 'Vegetative' THEN 0 ELSE 1 END
                            FROM farm_records WHERE farm_records.id = predictions.farm_record_id)";

        $predictions = $query
            ->whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('predictions')
                    ->where('model_type', 'XGBoost')
                    ->groupBy('farm_record_id');
            })
            ->orderByRaw("{$statusPriority} ASC")
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        // ── Aux dropdown data ──
        $availableYears = FarmRecord::query()
            ->when($user->role === 'farmer', function ($q) use ($user) {
                $q->whereIn('farm_id', Farm::where('user_id', $user->id)->pluck('id'));
            })
            ->select('year')->distinct()->orderBy('year', 'desc')
            ->pluck('year')->filter();

        $varieties = RiceVariety::orderBy('name')->get(['id', 'name']);

        $barangays = Farm::query()
            ->when($user->role === 'farmer', fn ($q) => $q->where('user_id', $user->id))
            ->select('barangay')->distinct()->orderBy('barangay')
            ->pluck('barangay')->filter();

        $pendingFarmRecords = FarmRecord::where('status', 'Vegetative')
            ->whereDoesntHave('predictions', fn ($q) => $q->where('model_type', 'XGBoost'))
            ->when($user->role === 'farmer', function ($q) use ($user) {
                $q->whereIn('farm_id', Farm::where('user_id', $user->id)->pluck('id'));
            })
            ->count();

        return view('admin.predictions.index', compact(
            'predictions',
            'pendingFarmRecords',
            'availableYears',
            'varieties',
            'barangays',
            'sort',
            'direction',
            'totalPredictions',
            'avgCityYield',
            'avgConfidence',
        ));
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
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
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
        if (!in_array(auth()->user()->role, ['admin', 'staff'])) {
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
            $result = $this->predictionService->predict($record, persist: true);
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