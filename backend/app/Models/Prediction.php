<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_record_id',
        'model_type',
        'predicted_yield_tons_ha',
        'confidence',
        'yield_lower',
        'yield_upper',
        'input_features',
    ];

    protected $casts = [
        'predicted_yield_tons_ha' => 'float',
        'confidence'              => 'float',
        'yield_lower'             => 'float',
        'yield_upper'             => 'float',
        'input_features'          => 'string',
    ];

    public function farmRecord()
    {
        return $this->belongsTo(FarmRecord::class);
    }
}