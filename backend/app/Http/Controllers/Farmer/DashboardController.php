<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmRecord;
use App\Services\PredictionService;
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

        // ── Empty state ─────────────────────────────────────────
        if ($farms->isEmpty()) {
            return view('farmer.dashboard', [
                'user'                          => $user,
                'farms'                         => $farms,
                'focusFarm'                     => null,
                'lastHarvest'                   => null,
                'lastPred'                      => null,
                'thisSeason'                    => null,
                'thisPred'                      => null,
                'recommendations'               => [],
                'recommendationsGeneratedAt'    => null,
                'totalTons'                     => null,
                'expectedTons'                  => null,
                'nextSeason'                    => $this->nextSeasonFor(null),
            ]);
        }

        // ── Focus farm selection ────────────────────────────────
        // Farmers can switch farms via ?farm=N; otherwise we auto-pick
        // the farm with the most recent seasonal activity.
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
                        'items'        => [],   // fail soft — dashboard still renders
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
            'nextSeason'
        ));
    }

    /**
     * Determine which season to plan for next.
     *
     * Priority:
     *   1. If there's an active season, pick the opposite one.
     *   2. Otherwise, infer from the current calendar month.
     *
     * Philippines (Santiago City):
     *   - Wet Season: roughly May/June → October/November
     *   - Dry Season: roughly November/December → April/May
     */
    private function nextSeasonFor(?FarmRecord $currentSeason): string
    {
        if ($currentSeason && in_array($currentSeason->season, ['Dry Season', 'Wet Season'], true)) {
            return $currentSeason->season === 'Dry Season' ? 'Wet Season' : 'Dry Season';
        }

        // No active season → base it on today's date.
        // Months 5–10 (May–Oct) = Wet Season is ongoing → next is Dry.
        // Months 11–4 (Nov–Apr) = Dry Season is ongoing → next is Wet.
        $month = (int) now()->month;

        return ($month >= 5 && $month <= 10) ? 'Dry Season' : 'Wet Season';
    }
}