<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'rice_variety_id',
        'season',
        'year',
        'fertilizer_kg_ha',
        'historical_yield_tons_ha',
        'actual_yield_tons_ha',
        'seeding_method',
        'water_source',
        'status',

        'weather_temperature',
        'weather_rainfall',
        'weather_humidity',
        'weather_captured_at',

        'harvest_weather_temperature',
        'harvest_weather_rainfall',
        'harvest_weather_humidity',
        'harvest_weather_captured_at',
    ];

    protected $casts = [
        'weather_captured_at'         => 'datetime',
        'harvest_weather_captured_at' => 'datetime',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function riceVariety()
    {
        return $this->belongsTo(RiceVariety::class);
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    // ═══════════════════════════════════════════════════════════
    // PREDICTION HELPERS
    // ═══════════════════════════════════════════════════════════

    /** Latest XGBoost prediction (already eager-loaded in the controller). */
    public function getLatestXgboostPredictionAttribute()
    {
        return $this->predictions
            ->where('model_type', 'XGBoost')
            ->sortByDesc('created_at')
            ->first();
    }

    /** Variety's average yield for the chosen seeding method. */
    public function getVarietyAvgYieldAttribute()
    {
        if (!$this->riceVariety) return null;
        $method = $this->seeding_method ?? 'Transplanted';
        $yield  = $this->riceVariety->getYieldForMethod($method);
        return $yield->avg ?? ($this->riceVariety->avg_yield ?? null);
    }

    /**
     * App-level yield class based on predicted yield vs variety average.
     * NOT a model output — derived in the application.
     */
    public function getPredictedYieldClassAttribute()
    {
        $pred = $this->latest_xgboost_prediction;
        $avg  = $this->variety_avg_yield;

        if (!$pred || !$avg || $avg <= 0) return null;

        $ratio = $pred->predicted_yield_tons_ha / $avg;
        if ($ratio >= 1.125) return 'High';
        if ($ratio >= 0.875) return 'Medium';
        return 'Low';
    }

    /** Ratio of predicted yield to variety average. */
    public function getPredictedYieldRatioAttribute()
    {
        $pred = $this->latest_xgboost_prediction;
        $avg  = $this->variety_avg_yield;
        if (!$pred || !$avg || $avg <= 0) return null;
        return $pred->predicted_yield_tons_ha / $avg;
    }

    /** Badge colour token for the yield class ('high' / 'medium' / 'low'). */
    public function getPredictedYieldBadgeAttribute()
    {
        return match ($this->predicted_yield_class) {
            'High'   => 'high',
            'Medium' => 'medium',
            'Low'    => 'low',
            default  => null,
        };
    }

    // ═══════════════════════════════════════════════════════════
    // DISPLAY HELPERS
    // ═══════════════════════════════════════════════════════════

    /** Short season label used for filtering and pills. */
public function getSeasonLabelAttribute(): string
{
    $raw = trim((string) ($this->season ?? ''));

    return match (true) {
        str_contains($raw, 'Dry') => 'Dry',
        str_contains($raw, 'Wet') => 'Wet',
        str_contains($raw, 'Off') => 'Off',
        default                   => $raw !== '' ? $raw : '—',
    };
}

    /** Colour token for the season pill ('dry' / 'wet' / 'off'). */
    public function getSeasonBadgeAttribute(): ?string
    {
        return match ($this->season) {
            'Dry Season' => 'dry',
            'Wet Season' => 'wet',
            'Off-Season' => 'off',
            default      => null,
        };
    }

    /** Colour token for the status pill ('growing' / 'harvested'). */
    public function getStatusBadgeAttribute(): ?string
    {
        return match ($this->status) {
            'Vegetative' => 'growing',
            'Harvested'  => 'harvested',
            default      => null,
        };
    }

    /** Human-readable status label. */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'Vegetative' => 'Growing',
            'Harvested'  => 'Harvested',
            default      => $this->status ?? '—',
        };
    }

    /** Yield to display on cards/tables (actual if harvested, historical otherwise). */
    public function getDisplayYieldAttribute()
    {
        if ($this->status === 'Harvested') {
            return $this->actual_yield_tons_ha ?? $this->historical_yield_tons_ha;
        }
        return $this->historical_yield_tons_ha;
    }

    /** Column label that matches display yield. */
    public function getYieldLabelAttribute(): string
    {
        return $this->status === 'Harvested' ? 'Actual Yield' : 'Historical Yield';
    }
}