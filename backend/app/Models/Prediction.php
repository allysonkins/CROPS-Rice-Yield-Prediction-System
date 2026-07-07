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
        'input_features',
    ];

    public function farmRecord()
    {
        return $this->belongsTo(FarmRecord::class);
    }
}