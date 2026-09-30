<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\FarmRecord;
use App\Models\Prediction;
use App\Models\RiceVariety;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Run prediction for a farm record.
     *
     * @return array{
     *     success: bool,
     *     error?: string,
     *     yield?: float,
     *     confidence?: float|null,
     *     yield_lower?: float|null,
     *     yield_upper?: float|null,
     *     prediction?: Prediction|null
     * }
     */
    public function predict(FarmRecord $farmRecord, bool $persist = true): array
    {
        $farmRecord->loadMissing(['farm', 'riceVariety']);

        $weather = $this->weatherService->getWeather();
        $variety = $farmRecord->riceVariety;
        $farm    = $farmRecord->farm;

        if (!$variety || !$farm) {
            return ['success' => false, 'error' => 'Missing farm or variety data.'];
        }

        $seasonMap = [
            'Dry Season' => 'Dry',
            'Wet Season' => 'Wet',
            'Dry'        => 'Dry',
            'Wet'        => 'Wet',
        ];
        if (!isset($seasonMap[$farmRecord->season])) {
            return ['success' => false, 'error' => 'Season not supported by model.'];
        }
        $season = $seasonMap[$farmRecord->season];

        $seedingMap = [
            'Transplanted'  => 'Transplanted',
            'Direct Seeded' => 'Direct Seeded',
            'Direct-Seeded' => 'Direct Seeded',
        ];
        $seeding = $seedingMap[$farmRecord->seeding_method ?? 'Transplanted'] ?? 'Transplanted';
        $isT     = $seeding === 'Transplanted';

        $maxYield = (float) ($variety->getMaxYieldForMethod($seeding) ?? 8.0);

        $avgYield = $isT
            ? (float) ($variety->avg_yield_transplanted ?? $variety->avg_yield ?? ($maxYield * 0.7))
            : (float) ($variety->avg_yield_direct ?? $variety->avg_yield ?? ($maxYield * 0.7));
        if (!$avgYield) {
            $avgYield = round($maxYield * 0.7, 2);
        }

        $maturity = (int) ($isT
            ? ($variety->growth_period_transplanted ?? 112)
            : ($variety->growth_period_direct ?? 112));

        $fertilizer = max(0, min((float) $farmRecord->fertilizer_kg_ha, 250));

        $historical = $farmRecord->historical_yield_tons_ha;
        $historical = ($historical === null || $historical == 0)
            ? $avgYield
            : max($avgYield * 0.40, min((float) $historical, $maxYield));

        $input = [
            'variety'                  => $variety->name,
            'classification'           => $variety->classification ?? 'Inbred',
            'soil_type'                => $farm->soil_type,
            'season'                   => $season,
            'seeding_method'           => $seeding,
            'variety_maturity_days'    => $maturity,
            'variety_max_yield'        => $maxYield,
            'variety_avg_yield'        => $avgYield,
            'land_area_ha'             => (float) ($farm->land_area_ha ?? 1.0),
            'fertilizer_kg_ha'         => $fertilizer,
            'historical_yield_tons_ha' => $historical,
            'temperature_avg'          => (float) ($weather['temperature'] ?? 28),
            'rainfall_mm'              => (float) ($weather['rainfall'] ?? 0),
            'humidity_avg'             => (float) ($weather['humidity'] ?? 75),
        ];

        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/predict', $input);
            $result   = $response->json();

            $yield      = $result['Predicted_Yield'] ?? null;
            $confidence = $result['Confidence']      ?? null;
            $yieldLow   = $result['Yield_Lower']     ?? null;
            $yieldHigh  = $result['Yield_Upper']     ?? null;

            if ($yield === null) {
                throw new \Exception('ML service did not return a valid prediction.');
            }

            if ($maxYield > 0 && $yield > $maxYield) {
                $yield = $maxYield;
            }
            if ($yieldLow !== null)  $yieldLow  = min((float) $yieldLow,  (float) $yield);
            if ($yieldHigh !== null) $yieldHigh = max((float) $yieldHigh, (float) $yield);

            $prediction = null;

            if ($persist && $farmRecord->exists) {
                $prediction = Prediction::create([
                    'farm_record_id'          => $farmRecord->id,
                    'model_type'              => 'XGBoost',
                    'predicted_yield_tons_ha' => $yield,
                    'confidence'              => $confidence,
                    'yield_lower'             => $yieldLow,
                    'yield_upper'             => $yieldHigh,
                    'input_features'          => json_encode([
                        'input'   => $input,
                        'weather' => $weather,
                    ]),
                ]);
            }

            return [
                'success'     => true,
                'yield'       => (float) $yield,
                'confidence'  => $confidence !== null ? (float) $confidence : null,
                'yield_lower' => $yieldLow,
                'yield_upper' => $yieldHigh,
                'prediction'  => $prediction,
            ];

        } catch (\Exception $e) {
            Log::warning('Prediction failed', [
                'farm_record_id' => $farmRecord->id ?? null,
                'variety'        => $variety->name ?? null,
                'error'          => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rank all active rice varieties for a given farm + season.
     */
    public function recommendForFarm(Farm $farm, string $season = 'Wet Season', int $limit = 3): array
    {
        $varieties = RiceVariety::whereNull('deleted_at')->orderBy('name')->get();

        $lastRecord = $farm->farmRecords()->latest()->first();

        $baseHistorical = $lastRecord?->historical_yield_tons_ha
            ?? $farm->farmRecords()->whereNotNull('actual_yield_tons_ha')->avg('actual_yield_tons_ha')
            ?? 5.0;

        $baseFertilizer = $lastRecord?->fertilizer_kg_ha ?? 120.0;
        $baseMethod     = $lastRecord?->seeding_method ?? 'Transplanted';

        $ranked = [];

        foreach ($varieties as $variety) {
            $fake = new FarmRecord([
                'farm_id'                  => $farm->id,
                'rice_variety_id'          => $variety->id,
                'season'                   => $season,
                'year'                     => now()->year,
                'fertilizer_kg_ha'         => $baseFertilizer,
                'historical_yield_tons_ha' => $baseHistorical,
                'seeding_method'           => $baseMethod,
                'status'                   => 'Vegetative',
            ]);
            $fake->setRelation('farm', $farm);
            $fake->setRelation('riceVariety', $variety);

            $result = $this->predict($fake, persist: false);

            if (!($result['success'] ?? false)) {
                continue;
            }

            $ranked[] = [
                'variety_id'             => $variety->id,
                'variety_name'           => $variety->name,
                'variety_classification' => $variety->classification,
                'yield'                  => (float) $result['yield'],
                'confidence'             => $result['confidence'] !== null ? (float) $result['confidence'] : null,
                'yield_lower'            => $result['yield_lower'] ?? null,
                'yield_upper'            => $result['yield_upper'] ?? null,
            ];
        }

        usort($ranked, fn($a, $b) => $b['yield'] <=> $a['yield']);

        return array_slice($ranked, 0, $limit);
    }

    public function deleteForFarmRecord(int $farmRecordId): int
    {
        return Prediction::where('farm_record_id', $farmRecordId)
            ->where('model_type', 'XGBoost')
            ->delete();
    }
}