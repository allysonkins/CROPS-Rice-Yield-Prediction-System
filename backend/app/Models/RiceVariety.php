<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiceVariety extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'classification',
        'growth_period',
        'disease_susceptibility',
        'optimal_temp_min',
        'optimal_temp_max',
    ];
}