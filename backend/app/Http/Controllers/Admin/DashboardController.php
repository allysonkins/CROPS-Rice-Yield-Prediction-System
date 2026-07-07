<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use App\Models\RiceVariety;
use App\Models\Prediction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Block non-admin users
        if (auth()->user()->role !== 'admin') {
        abort(403, 'You are not authorized to view this page.');
        }
        
        // 1. Get statistics
        $farmerCount = User::where('role', 'farmer')->count();
        $farmCount = Farm::count();
        $varietyCount = RiceVariety::count();

        // 2. Get Average Yield (Handle null properly)
        $avgYieldData = Prediction::where('model_type', 'Ensemble')
            ->selectRaw('AVG(predicted_yield_tons_ha) as avg_yield')
            ->first();

        // If we have data and the average is not null, format it; otherwise, show 'N/A'
        if ($avgYieldData && $avgYieldData->avg_yield !== null) {
            $avgYield = number_format($avgYieldData->avg_yield, 2);
        } else {
            $avgYield = 'N/A';
        }

        // 3. Get low yield farms (less than 4 tons/ha)
        $lowYieldFarms = Farm::with('user')
            ->whereHas('farmRecords', function($q) {
                $q->whereHas('predictions', function($sub) {
                    $sub->where('model_type', 'Ensemble')
                        ->where('predicted_yield_tons_ha', '<', 4.0);
                });
            })
            ->get();

        // 4. Pass everything to the view
        return view('admin.dashboard', compact(
            'farmerCount', 
            'farmCount', 
            'varietyCount', 
            'avgYield', 
            'lowYieldFarms'
        ));
    }
} 