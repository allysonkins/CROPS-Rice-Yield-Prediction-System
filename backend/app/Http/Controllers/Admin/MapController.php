<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;

class MapController extends Controller
{
    public function index()
    {
        // Get all farms with their latest predictions
        $farms = Farm::with(['user', 'farmRecords.predictions'])->get();
        
        // Prepare data for the map
        $farmData = $farms->map(function($farm) {
            // Get the latest Ensemble prediction for this farm
            $latestPrediction = $farm->farmRecords
                ->flatMap(function($record) {
                    return $record->predictions->where('model_type', 'Ensemble');
                })
                ->last();
            
            return [
                'id' => $farm->id,
                'name' => $farm->name,
                'barangay' => $farm->barangay,
                'farmer' => $farm->user->name ?? 'Unknown',
                'lat' => $farm->latitude,
                'lng' => $farm->longitude,
                'yield' => $latestPrediction ? round($latestPrediction->predicted_yield_tons_ha, 2) : null,
                'land_area' => $farm->land_area_ha,
                'soil_type' => $farm->soil_type,
            ];
        });

        return view('admin.map.index', compact('farmData'));
    }
}