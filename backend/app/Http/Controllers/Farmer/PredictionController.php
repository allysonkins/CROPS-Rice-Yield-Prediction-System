<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;

class PredictionController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get farmer's farm record IDs
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');
        $farmRecordIds = FarmRecord::whereIn('farm_id', $farmIds)->pluck('id');

        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->where('model_type', 'RandomForest')
            ->whereIn('farm_record_id', $farmRecordIds)
            ->orderBy('updated_at', 'desc')
            ->get();

        $stats = [
            'total' => $predictions->count(),
            'avg_yield' => $predictions->avg('predicted_yield_tons_ha'),
            'max_yield' => $predictions->max('predicted_yield_tons_ha'),
            'min_yield' => $predictions->min('predicted_yield_tons_ha'),
        ];

        return view('farmer.predictions', compact('predictions', 'stats'));
    }
}