<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;
use Illuminate\Http\Request;

class PredictionComparisonController extends Controller
{
    public function index()
    {
        $farmIds = Farm::where('user_id', auth()->id())->pluck('id');

        $years = FarmRecord::whereIn('farm_id', $farmIds)
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->map(fn ($y) => (int) $y)
            ->values();

        $farms = Farm::where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'barangay']);

        return view('farmer.compare', compact('years', 'farms'));
    }

    public function data(Request $request)
    {
        $request->validate([
            'years'   => 'required|array|min:2|max:10',
            'years.*' => 'integer|min:2000|max:2100',
            'farm_id' => 'nullable|integer|exists:farms,id',
        ]);

        $farmIds = Farm::where('user_id', auth()->id())->pluck('id');
        $years   = array_map('intval', $request->input('years'));
        $farmId  = $request->input('farm_id');

        if ($farmId && !$farmIds->contains($farmId)) {
            return response()->json([
                'success' => false,
                'error'   => 'You can only compare your own farms.',
            ], 403);
        }

        $records = FarmRecord::query()
            ->with(['riceVariety', 'farm'])
            ->whereIn('farm_id', $farmIds)
            ->whereIn('year', $years)
            ->when($farmId, fn ($q) => $q->where('farm_id', $farmId))
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success'      => true,
                'labels'       => [],
                'predicted'    => [],
                'actual'       => [],
                'meta'         => [],
                'perYear'      => [],
                'overall'      => null,
                'missingYears' => $years,
            ]);
        }

        $recordIds = $records->pluck('id');

        $predictions = Prediction::whereIn('farm_record_id', $recordIds)
            ->where('model_type', 'XGBoost')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('farm_record_id')
            ->keyBy('farm_record_id');

        $grouped = $records->groupBy('year');

        $labels       = [];
        $predicted    = [];
        $actual       = [];
        $meta         = [];
        $perYear      = [];
        $missingYears = [];

        $totalMatched = 0;
        $totalAbsErr  = 0.0;

        foreach ($years as $year) {
            $yearRecords = $grouped->get($year);

            if (!$yearRecords || $yearRecords->isEmpty()) {
                $missingYears[] = $year;
                $labels[]       = (string) $year;
                $predicted[]    = null;
                $actual[]       = null;
                $meta[]         = null;
                $perYear[]      = [
                    'year' => $year, 'records' => 0,
                    'predicted' => 0, 'actual' => 0, 'matched' => 0,
                    'avgPred' => null, 'avgAct' => null, 'delta' => null,
                ];
                continue;
            }

            $yearPred = $yearRecords
                ->map(fn ($r) => $predictions->get($r->id)?->predicted_yield_tons_ha)
                ->filter(fn ($v) => $v !== null);

            $yearActual = $yearRecords
                ->where('status', 'Harvested')
                ->pluck('actual_yield_tons_ha')
                ->filter(fn ($v) => $v !== null);

            $avgPred = $yearPred->isNotEmpty() ? round($yearPred->avg(), 2) : null;
            $avgAct  = $yearActual->isNotEmpty() ? round($yearActual->avg(), 2) : null;

            $matched = 0; $sumAbs = 0.0;
            foreach ($yearRecords as $r) {
                $pred    = $predictions->get($r->id)?->predicted_yield_tons_ha;
                $actual_ = $r->actual_yield_tons_ha;
                if ($pred !== null && $actual_ !== null) {
                    $sumAbs += abs($pred - $actual_);
                    $matched++;
                }
            }

            $totalMatched += $matched;
            $totalAbsErr  += $sumAbs;

            $labels[]    = (string) $year;
            $predicted[] = $avgPred;
            $actual[]    = $avgAct;

            $delta = ($avgPred !== null && $avgAct !== null)
                ? round($avgAct - $avgPred, 2)
                : null;

            $meta[] = [
                'records' => $yearRecords->count(),
                'matched' => $matched,
            ];

            $perYear[] = [
                'year'      => $year,
                'records'   => $yearRecords->count(),
                'predicted' => $yearPred->count(),
                'actual'    => $yearActual->count(),
                'matched'   => $matched,
                'avgPred'   => $avgPred,
                'avgAct'    => $avgAct,
                'delta'     => $delta,
            ];
        }

        $overall = [
            'matched' => $totalMatched,
            'mae'     => $totalMatched > 0 ? round($totalAbsErr / $totalMatched, 2) : null,
            'years'   => count($years),
        ];

        return response()->json([
            'success'      => true,
            'labels'       => $labels,
            'predicted'    => $predicted,
            'actual'       => $actual,
            'meta'         => $meta,
            'perYear'      => $perYear,
            'overall'      => $overall,
            'missingYears' => $missingYears,
        ]);
    }
}