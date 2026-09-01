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
        'fertilizer_kg_ha',
        'historical_yield_tons_ha',
        'actual_yield_tons_ha',
        'seeding_method',
        'status',
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

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'Harvested' => 'bg-success',
            'Vegetative' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute()
    {
        return match ($this->status) {
            'Harvested' => 'bi-check-circle-fill',
            'Vegetative' => 'bi-tree-fill',
            default => 'bi-question-circle',
        };
    }

    /**
     * Get the display yield based on status.
     */
    public function getDisplayYieldAttribute()
    {
        if ($this->status === 'Harvested') {
            return $this->actual_yield_tons_ha ?? $this->historical_yield_tons_ha;
        }
        return $this->historical_yield_tons_ha;
    }

    /**
     * Get yield label based on status.
     */
    public function getYieldLabelAttribute()
    {
        return $this->status === 'Harvested' ? 'Actual Yield' : 'Historical Yield';
    }
}