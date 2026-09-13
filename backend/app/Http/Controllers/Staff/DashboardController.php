<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use App\Models\FarmRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Only allow staff or admin to view this page
        if (!in_array(auth()->user()->role, ['staff', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }

        // Statistics
        $totalFarms = Farm::count();
        $totalFarmers = User::where('role', 'farmer')->count();
        $totalVarieties = RiceVariety::count();

        // --- Get latest Random Forest prediction per farm record ---
        $allPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->orderBy('created_at', 'desc')
            ->get();

        $latestPredictions = $allPredictions->unique('farm_record_id');

        // Average yield from Random Forest (latest per farm record)
        $rawAvg = $latestPredictions->avg('predicted_yield_tons_ha');

        // Fallback: harvested actual yields if no predictions exist
        if ($rawAvg === null) {
            $rawAvg = FarmRecord::where('status', 'Harvested')
                ->whereNotNull('actual_yield_tons_ha')
                ->avg('actual_yield_tons_ha');
        }

        $avgYield = $rawAvg !== null ? number_format($rawAvg, 2) : 'No data';

        // --- Low yield predictions: below 70% of the variety's max for the seeding method ---
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
            return $ratio < 0.7;
        })->sortBy('predicted_yield_tons_ha')->values();

        // --- Recent predictions (Random Forest, latest 5) ---
        $recentPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('staff.dashboard', compact(
            'totalFarms',
            'totalFarmers',
            'totalVarieties',
            'avgYield',
            'lowYieldFarms',
            'recentPredictions'
        ));
    }
}