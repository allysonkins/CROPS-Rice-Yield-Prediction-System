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

        if ($user->role === 'farmer') {
            $farms = Farm::with('user')
                ->where('user_id', $user->id)
                ->get();
        } else {
            $farms = Farm::with('user')->get();
        }

        $farmData = $farms->map(function ($farm) {
            // Latest XGBoost prediction for ANY farm record on this farm
            $latestPrediction = Prediction::where('model_type', 'XGBoost')
                ->with('farmRecord.riceVariety')
                ->whereHas('farmRecord', fn($q) => $q->where('farm_id', $farm->id))
                ->latest('created_at')
                ->first();

            $yield      = $latestPrediction?->predicted_yield_tons_ha;
            $confidence = $latestPrediction?->confidence;
            $lo         = $latestPrediction?->yield_lower;
            $hi         = $latestPrediction?->yield_upper;

            // ── App-level yield class (variety-relative, NOT a model output) ──
            $yieldClass  = null;
            $ratio       = null;
            $varietyAvg  = null;
            $seedingMethod = null;

            if ($latestPrediction && $latestPrediction->farmRecord?->riceVariety) {
                $fr     = $latestPrediction->farmRecord;
                $method = $fr->seeding_method ?? 'Transplanted';
                $vy     = $fr->riceVariety->getYieldForMethod($method);

                $varietyAvg    = $vy->avg ?? ($fr->riceVariety->avg_yield ?? null);
                $seedingMethod = $method;

                if ($varietyAvg && $varietyAvg > 0 && $yield !== null) {
                    $ratio = $yield / $varietyAvg;
                    if ($ratio >= 1.125)     $yieldClass = 'High';
                    elseif ($ratio >= 0.875) $yieldClass = 'Medium';
                    else                     $yieldClass = 'Low';
                }
            }

            return [
                'id'             => $farm->id,
                'name'           => $farm->name,
                'barangay'       => $farm->barangay,
                'farmer'         => $farm->user->name ?? 'Unassigned',
                'lat'            => $farm->latitude,
                'lng'            => $farm->longitude,
                'land_area'      => $farm->land_area_ha,
                'soil_type'      => $farm->soil_type,
                'yield'          => $yield !== null ? round($yield, 2) : null,
                'class'          => $yieldClass,
                'confidence'     => $confidence,
                'yield_lower'    => $lo,
                'yield_upper'    => $hi,
                'variety_avg'    => $varietyAvg,
                'ratio'          => $ratio,
                'seeding_method' => $seedingMethod,
            ];
        });

        return view('admin.map.index', compact('farmData'));
    }
}