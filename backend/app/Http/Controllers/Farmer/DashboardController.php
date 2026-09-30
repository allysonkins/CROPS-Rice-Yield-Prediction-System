<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Services\PredictionService;
use App\Services\WeatherService;
use App\Support\RecommendationCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(PredictionService $predictionService)
    {
        $user = auth()->user();

        $farms = Farm::where('user_id', $user->id)
            ->withCount('farmRecords')
            ->orderBy('name')
            ->get();

        $palayPricePerKg = (int) config('santiago.palay_price_per_kg', 20);

        // ── Weather (OpenWeatherMap — Santiago City) ────────────
        // Fetched once, used in both the empty-state and the main view.
        $weather = null;
        try {
            $weather = app(WeatherService::class)->getWeather();
        } catch (\Exception $e) {
            \Log::warning('Weather fetch failed on farmer dashboard', [
                'error' => $e->getMessage(),
            ]);
        }

        // ── Empty state ─────────────────────────────────────────
        if ($farms->isEmpty()) {
            return view('farmer.dashboard', [
                'user'                       => $user,
                'farms'                      => $farms,
                'focusFarm'                  => null,
                'lastHarvest'                => null,
                'lastPred'                   => null,
                'thisSeason'                 => null,
                'thisPred'                   => null,
                'recommendations'            => [],
                'recommendationsGeneratedAt' => null,
                'totalTons'                  => null,
                'expectedTons'               => null,
                'nextSeason'                 => $this->nextSeasonFor(null),
                'palayPricePerKg'            => $palayPricePerKg,
                'incomeSeasonLabel'          => null,
                'incomeSeasonYear'           => null,
                'incomeFarmCount'            => 0,
                'incomeExpectedCavan'        => 0,
                'incomeActualCavan'          => 0,
                'weather'                    => $weather,
            ]);
        }

        // ── Focus farm selection ────────────────────────────────
        $requestedFarmId = (int) request()->query('farm');

        if ($requestedFarmId && $farms->contains('id', $requestedFarmId)) {
            $focusFarm = $farms->firstWhere('id', $requestedFarmId);
        } else {
            $focusFarm = FarmRecord::whereIn('farm_id', $farms->pluck('id'))
                ->latest()
                ->first()?->farm
                ?? $farms->first();
        }

        // ── Last harvest ────────────────────────────────────────
        $lastHarvest = FarmRecord::where('farm_id', $focusFarm->id)
            ->where('status', 'Harvested')
            ->whereNotNull('actual_yield_tons_ha')
            ->with(['riceVariety', 'predictions'])
            ->orderBy('year', 'desc')
            ->orderBy('updated_at', 'desc')
            ->first();

        $lastPred = $lastHarvest
            ? $lastHarvest->predictions->sortByDesc('created_at')->first()
            : null;

        $totalTons = $lastHarvest && $lastHarvest->actual_yield_tons_ha
            ? round($lastHarvest->actual_yield_tons_ha * $focusFarm->land_area_ha, 2)
            : null;

        // ── This season (most recent Vegetative) ────────────────
        $thisSeason = FarmRecord::where('farm_id', $focusFarm->id)
            ->where('status', 'Vegetative')
            ->with(['riceVariety', 'predictions'])
            ->orderBy('year', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $thisPred = $thisSeason
            ? $thisSeason->predictions->sortByDesc('created_at')->first()
            : null;

        $expectedTons = $thisPred && $thisPred->predicted_yield_tons_ha
            ? round($thisPred->predicted_yield_tons_ha * $focusFarm->land_area_ha, 2)
            : null;

        // ── Next season target ──────────────────────────────────
        $nextSeason = $this->nextSeasonFor($thisSeason);

        // ── Season-wide tantyang kita (across ALL farms) ────────
        $incomeSeasonLabel = $thisSeason?->season ?? $lastHarvest?->season;
        $incomeSeasonYear  = $thisSeason?->year   ?? $lastHarvest?->year;

        $incomeExpectedCavan = 0;
        $incomeActualCavan   = 0;
        $incomeFarmCount     = 0;

        if ($incomeSeasonLabel && $incomeSeasonYear) {
            $seasonRecords = FarmRecord::whereIn('farm_id', $farms->pluck('id'))
                ->where('season', $incomeSeasonLabel)
                ->where('year', $incomeSeasonYear)
                ->with(['farm', 'predictions'])
                ->get();

            $incomeFarmCount = $seasonRecords->pluck('farm_id')->unique()->count();

            foreach ($seasonRecords as $rec) {
                $area = (float) ($rec->farm->land_area_ha ?? 0);

                if ($rec->actual_yield_tons_ha !== null) {
                    $incomeActualCavan += t_ha_to_cavan_ha((float) $rec->actual_yield_tons_ha) * $area;
                }

                $latestPred = $rec->predictions->sortByDesc('created_at')->first();
                if ($latestPred && $latestPred->predicted_yield_tons_ha !== null) {
                    $incomeExpectedCavan += t_ha_to_cavan_ha((float) $latestPred->predicted_yield_tons_ha) * $area;
                }
            }
        }

        // ── Recommendations (cached per farm + season + global version) ──
        $cacheKey = RecommendationCache::key($focusFarm->id, $nextSeason)
            . '.g' . RecommendationCache::globalVersion();

        $cached = Cache::remember(
            $cacheKey,
            now()->addHours(RecommendationCache::TTL_HOURS),
            function () use ($predictionService, $focusFarm, $nextSeason) {
                try {
                    return [
                        'generated_at' => now()->toIso8601String(),
                        'items'        => $predictionService->recommendForFarm($focusFarm, $nextSeason, 3),
                    ];
                } catch (\Exception $e) {
                    \Log::warning('Recommendation generation failed', [
                        'farm_id' => $focusFarm->id,
                        'season'  => $nextSeason,
                        'error'   => $e->getMessage(),
                    ]);

                    return [
                        'generated_at' => now()->toIso8601String(),
                        'items'        => [],
                    ];
                }
            }
        );

        $recommendations            = $cached['items'] ?? [];
        $recommendationsGeneratedAt = !empty($cached['generated_at'])
            ? Carbon::parse($cached['generated_at'])
            : null;

        return view('farmer.dashboard', compact(
            'user',
            'farms',
            'focusFarm',
            'lastHarvest',
            'lastPred',
            'thisSeason',
            'thisPred',
            'recommendations',
            'recommendationsGeneratedAt',
            'totalTons',
            'expectedTons',
            'nextSeason',
            'palayPricePerKg',
            'incomeSeasonLabel',
            'incomeSeasonYear',
            'incomeFarmCount',
            'incomeExpectedCavan',
            'incomeActualCavan',
            'weather'
        ));
    }

    private function nextSeasonFor(?FarmRecord $currentSeason): string
    {
        if ($currentSeason && in_array($currentSeason->season, ['Dry Season', 'Wet Season'], true)) {
            return $currentSeason->season === 'Dry Season' ? 'Wet Season' : 'Dry Season';
        }

        $month = (int) now()->month;
        return ($month >= 5 && $month <= 10) ? 'Dry Season' : 'Wet Season';
    }
}