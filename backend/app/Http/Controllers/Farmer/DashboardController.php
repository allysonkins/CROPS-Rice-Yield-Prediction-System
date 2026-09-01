<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;
use App\Models\RiceVariety;
use App\Models\User;
use App\Models\FarmRecord;
use App\Models\Advisory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Only allow farmers
        if (auth()->user()->role !== 'farmer') {
            abort(403, 'Unauthorized access.');
        }

        $farmer = auth()->user();

        // Get farmer's farms
        $farms = Farm::where('user_id', $farmer->id)->get();
        $totalFarms = $farms->count();
        $totalArea = $farms->sum('land_area_ha');

        // Get varieties used by farmer
        $varietyIds = FarmRecord::whereIn('farm_id', $farms->pluck('id'))
            ->pluck('rice_variety_id')
            ->unique();
        $totalVarieties = RiceVariety::whereIn('id', $varietyIds)->count();

        // Average yield from farmer's farms (Random Forest)
        $farmRecordIds = FarmRecord::whereIn('farm_id', $farms->pluck('id'))->pluck('id');
        $avgYieldData = Prediction::where('model_type', 'RandomForest')
            ->whereIn('farm_record_id', $farmRecordIds)
            ->selectRaw('AVG(predicted_yield_tons_ha) as avg_yield')
            ->first();

        $avgYield = ($avgYieldData && $avgYieldData->avg_yield !== null)
            ? number_format($avgYieldData->avg_yield, 2)
            : 'N/A';

        // Recent predictions for farmer's farms (latest 5)
        $recentPredictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->whereIn('farm_record_id', $farmRecordIds)
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Advisories targeted at farmers
        $advisories = Advisory::where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'farmers')
                  ->orWhere('target_audience', 'all');
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return view('farmer.dashboard', compact(
            'farmer',
            'farms',
            'totalFarms',
            'totalArea',
            'totalVarieties',
            'avgYield',
            'recentPredictions',
            'advisories'
        ));
    }
}