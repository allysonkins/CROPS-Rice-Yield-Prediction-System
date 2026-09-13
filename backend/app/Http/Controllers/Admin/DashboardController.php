<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use App\Models\RiceVariety;
use App\Models\Prediction;
use App\Models\FarmRecord;

class DashboardController extends Controller
{
    public function index()
    {
        // Block non-admin users
        if (auth()->user()->role !== 'admin') {
            abort(403, 'You are not authorized to view this page.');
        }

        // 1. Statistics
        $farmerCount = User::where('role', 'farmer')->count();
        $farmCount = Farm::count();
        $varietyCount = RiceVariety::count();

        // 2. Average Yield — use latest Random Forest prediction per farm record
        $allPredictions = Prediction::with(['farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'desc')
            ->get();

        // Keep only latest per farm record
        $latestPredictions = $allPredictions->unique('farm_record_id');

        $rawAvg = $latestPredictions->avg('predicted_yield_tons_ha');

        // Fallback: if no RF predictions yet, use harvested actual yields
        if ($rawAvg === null) {
            $rawAvg = FarmRecord::where('status', 'Harvested')
                ->whereNotNull('actual_yield_tons_ha')
                ->avg('actual_yield_tons_ha');
        }

        $avgYield = $rawAvg !== null ? number_format($rawAvg, 2) : 'No data';

        // 3. Low Yield Areas — predictions below 70% of the variety's max yield
        $lowYieldFarms = $latestPredictions->filter(function ($pred) {
            $farmRecord = $pred->farmRecord;
            if (!$farmRecord || !$farmRecord->riceVariety) {
                return false;
            }
            $max = $farmRecord->riceVariety->getMaxYieldForMethod($farmRecord->seeding_method);
            if ($max === null || $max <= 0) {
                // fallback to global threshold
                return $pred->predicted_yield_tons_ha < 4.0;
            }
            $ratio = $pred->predicted_yield_tons_ha / $max;
            return $ratio < 0.7; // below 70% of variety potential = low
        })->sortBy('predicted_yield_tons_ha')->values();

        return view('admin.dashboard', compact(
            'farmerCount',
            'farmCount',
            'varietyCount',
            'avgYield',
            'lowYieldFarms'
        ));
    }
}