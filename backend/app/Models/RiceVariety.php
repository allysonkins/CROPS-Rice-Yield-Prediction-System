<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RiceVariety extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'classification',
        'growth_period',
        'growth_period_transplanted',
        'growth_period_direct',
        'disease_susceptibility',
        'optimal_temp_min',
        'optimal_temp_max',
        'description',
        'avg_yield',
        'max_yield',
        'avg_yield_transplanted',
        'max_yield_transplanted',
        'avg_yield_direct',
        'max_yield_direct',
        'grain_quality',
        'resilience',
    ];

    protected $casts = [
        'resilience' => 'array',
    ];

    // ---- Yield Helpers ----
    public function getYieldForMethod($method)
    {
        $avgKey = $method === 'Transplanted' ? 'avg_yield_transplanted' : 'avg_yield_direct';
        $maxKey = $method === 'Transplanted' ? 'max_yield_transplanted' : 'max_yield_direct';

        return (object) [
            'avg' => $this->$avgKey ?? null,
            'max' => $this->$maxKey ?? null,
        ];
    }

    // ---- Growth Period Helpers ----
    public function getGrowthPeriodForMethod($method)
    {
        $key = $method === 'Transplanted' ? 'growth_period_transplanted' : 'growth_period_direct';
        return $this->$key ?? $this->growth_period; // fallback to main growth_period
    }
}