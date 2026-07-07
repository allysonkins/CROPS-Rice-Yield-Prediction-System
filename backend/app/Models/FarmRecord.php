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
        'seeding_method',
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
}