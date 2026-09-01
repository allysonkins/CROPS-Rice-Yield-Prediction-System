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
     */
    public function index()
    {
        $user = auth()->user();

        // Get farms owned by this farmer
        $farmIds = Farm::where('user_id', $user->id)->pluck('id');

        // Get predictions from farm records that belong to these farms
        $predictions = Prediction::with(['farmRecord.farm', 'farmRecord.riceVariety'])
            ->whereHas('farmRecord', function ($query) use ($farmIds) {
                $query->whereIn('farm_id', $farmIds);
            })
            ->where('model_type', 'RandomForest')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Stats
        $stats = [
            'total' => $predictions->count(),
            'avg_yield' => $predictions->avg('predicted_yield_tons_ha'),
            'max_yield' => $predictions->max('predicted_yield_tons_ha'),
            'min_yield' => $predictions->min('predicted_yield_tons_ha'),
        ];

        return view('farmer.predictions', compact('predictions', 'stats'));
    }

    /**
     * Show a specific prediction in a modal (AJAX).
     */
    public function show($id)
    {
        $user = auth()->user();

        // Fetch the prediction with relationships
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
}