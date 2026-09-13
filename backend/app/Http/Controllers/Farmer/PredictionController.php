<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    /**
     * Display a listing of predictions for the authenticated farmer.
     * Read-only — farmers cannot generate or regenerate predictions.
     */
    public function index()
    {
        $user = auth()->user();

        // Get farms owned by this farmer
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');

        // Get latest prediction per farm record
        $allPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->whereHas('farmRecord', function ($query) use ($farmIds) {
                $query->whereIn('farm_id', $farmIds);
            })
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'desc')
            ->get();

        $predictions = $allPredictions->unique('farm_record_id')->values();

        $stats = [
            'total'     => $predictions->count(),
            'avg_yield' => $predictions->avg('predicted_yield_tons_ha'),
            'max_yield' => $predictions->max('predicted_yield_tons_ha'),
            'min_yield' => $predictions->min('predicted_yield_tons_ha'),
        ];

        return view('farmer.predictions', compact('predictions', 'stats'));
    }

    /**
     * Show a specific prediction in a modal (AJAX). Read-only.
     */
    public function show($id)
    {
        $user = auth()->user();

        $prediction = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->findOrFail($id);

        // Ensure the prediction belongs to one of the farmer's farms
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');
        if (!$farmIds->contains($prediction->farmRecord->farm_id)) {
            abort(403, 'You are not authorized to view this prediction.');
        }

        $farmRecord = $prediction->farmRecord;
        $weather = json_decode($prediction->input_features, true)['weather'] ?? null;

        return view('farmer.prediction-detail', compact('prediction', 'farmRecord', 'weather'));
    }

    /**
     * Show the full prediction history with trend analysis for a farm record.
     * Only accessible for the farmer's own farms.
     */
    public function history($id)
    {
        $user = auth()->user();

        $farmRecord = \App\Models\FarmRecord::with(['farm', 'riceVariety'])->findOrFail($id);

        // Authorisation — the farm must belong to the logged-in farmer
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');
        if (!$farmIds->contains($farmRecord->farm_id)) {
            abort(403, 'You are not authorized to view this prediction history.');
        }

        // Get predictions in chronological order (oldest first)
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

            $direction = 'stable';
            if ($count >= 2) {
                if ($latest > $first + 0.05) {
                    $direction = 'up';
                } elseif ($latest < $first - 0.05) {
                    $direction = 'down';
                }
            }

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

        return view('farmer.prediction-history', compact('farmRecord', 'predictions', 'analysis'));
    }
}