<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'barangay',
        'land_area_ha',
        'soil_type',
        'latitude',
        'longitude',
    ];

    // A farm belongs to a farmer (User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A farm has many farm records (seasons)
    public function farmRecords()
    {
        return $this->hasMany(FarmRecord::class);
    }
}