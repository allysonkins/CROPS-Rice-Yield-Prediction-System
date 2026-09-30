<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Farm;
use App\Models\RiceVariety;
use App\Models\Prediction;
use App\Models\FarmRecord;
use App\Services\WeatherService;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'You are not authorized to view this page.');
        }

        $farmerCount = User::where('role', 'farmer')->count();
        $farmCount   = Farm::count();
        $recordCount = FarmRecord::count();

        // ── Weather (OpenWeatherMap — Santiago City) ────────────
        $weather = null;
        try {
            $weather = app(WeatherService::class)->getWeather();
        } catch (\Exception $e) {
            \Log::warning('Weather fetch failed on admin dashboard', [
                'error' => $e->getMessage(),
            ]);
        }

        $allPredictions = Prediction::with([
                'farmRecord.farm',
                'farmRecord.riceVariety',
            ])
            ->where('model_type', 'XGBoost')
            ->orderBy('created_at', 'desc')
            ->get();

        $uniquePredictions = $allPredictions->unique('farm_record_id');

        $totalPred     = $uniquePredictions->count();
        $avgYield      = $uniquePredictions->whereNotNull('predicted_yield_tons_ha')->avg('predicted_yield_tons_ha');
        $avgConfidence = $uniquePredictions->whereNotNull('confidence')->count() > 0
            ? $uniquePredictions->whereNotNull('confidence')->avg('confidence')
            : null;

        // Low yield — below 87.5% of variety average
        $lowYieldPredictions = collect();
        foreach ($uniquePredictions as $p) {
            $yield = $p->predicted_yield_tons_ha;
            if ($yield === null) continue;

            $fr = $p->farmRecord;
            if (!$fr || !$fr->riceVariety) continue;

            $method = $fr->seeding_method ?? 'Transplanted';
            $vy     = $fr->riceVariety->getYieldForMethod($method);
            $avgY   = $vy->avg ?? ($fr->riceVariety->avg_yield ?? null);
            if (!$avgY || $avgY <= 0) continue;

            $ratio = $yield / $avgY;
            if ($ratio < 0.875) {
                $lowYieldPredictions->push([
                    'prediction' => $p,
                    'yield'      => $yield,
                    'avg'        => $avgY,
                    'ratio'      => $ratio,
                    'confidence' => $p->confidence,
                ]);
            }
        }
        $lowYieldPredictions = $lowYieldPredictions->sortBy('ratio')->values();
        $lowYieldCount = $lowYieldPredictions->count();
        $lowYieldPct   = $totalPred > 0 ? round(($lowYieldCount / $totalPred) * 100, 1) : 0;

        // Temporal trend
        $trendLabels = []; $trendData = [];
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = now()->subWeeks($i)->endOfWeek();
            $weekPreds = $uniquePredictions->filter(fn($p) => $p->created_at >= $start && $p->created_at <= $end);
            $trendLabels[] = $start->format('M d');
            $trendData[]   = $weekPreds->count() > 0
                ? round($weekPreds->avg('predicted_yield_tons_ha'), 2)
                : null;
        }

        // Spatial aggregation
        $barangayData = $uniquePredictions
            ->groupBy(fn($p) => $p->farmRecord->farm->barangay ?? 'Unknown')
            ->map(fn($g) => [
                'avg'   => round($g->avg('predicted_yield_tons_ha'), 2),
                'count' => $g->count(),
            ])
            ->filter(fn($d) => $d['avg'] !== null)
            ->sortByDesc('avg');

        $recent = $uniquePredictions->sortByDesc('created_at')->take(5);

        return view('admin.dashboard', compact(
            'farmerCount',
            'farmCount',
            'recordCount',
            'totalPred',
            'avgYield',
            'avgConfidence',
            'lowYieldPredictions',
            'lowYieldCount',
            'lowYieldPct',
            'trendLabels',
            'trendData',
            'barangayData',
            'recent',
            'weather'
        ));
    }
}