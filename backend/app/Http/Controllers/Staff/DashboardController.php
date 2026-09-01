<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
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

        // Average yield from Random Forest
        $avgYieldData = Prediction::where('model_type', 'RandomForest')
            ->selectRaw('AVG(predicted_yield_tons_ha) as avg_yield')
            ->first();
        $avgYield = ($avgYieldData && $avgYieldData->avg_yield !== null)
            ? number_format($avgYieldData->avg_yield, 2)
            : 'N/A';

        // Low yield farms (Random Forest predictions < 4.0)
        $lowYieldFarms = Farm::with(['user', 'farmRecords.predictions'])
            ->whereHas('farmRecords', function ($q) {
                $q->whereHas('predictions', function ($sub) {
                    $sub->where('model_type', 'RandomForest')
                        ->where('predicted_yield_tons_ha', '<', 4.0);
                });
            })
            ->get();

        // Recent predictions (Random Forest, latest 5)
        $recentPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->latest('updated_at')
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