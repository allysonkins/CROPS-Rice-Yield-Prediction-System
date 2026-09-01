<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Prediction;

class MapController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // If farmer, only show their farms
        if ($user->role === 'farmer') {
            $farms = Farm::with('user')
                ->where('user_id', $user->id)
                ->get();
        } else {
            $farms = Farm::with('user')->get();
        }

        $farmData = $farms->map(function($farm) {
            $latestPrediction = null;
            foreach ($farm->farmRecords as $record) {
                foreach ($record->predictions as $prediction) {
                    if ($prediction->model_type === 'RandomForest') {
                        $latestPrediction = $prediction;
                        break 2;
                    }
                }
            }

            return [
                'id' => $farm->id,
                'name' => $farm->name,
                'barangay' => $farm->barangay,
                'farmer' => $farm->user->name ?? 'Unassigned',
                'lat' => $farm->latitude,
                'lng' => $farm->longitude,
                'land_area' => $farm->land_area_ha,
                'soil_type' => $farm->soil_type,
                'yield' => $latestPrediction ? round($latestPrediction->predicted_yield_tons_ha, 2) : null,
            ];
        });

        return view('admin.map.index', compact('farmData'));
    }
}