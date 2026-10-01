<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerImportRow extends Model
{
    protected $fillable = [
        'batch_id','line','status','is_rice',
        'rsbsa_number','name','first_name','middle_name','last_name',
        'sex','phone','barangay',
        'parcel_no','parcel_barangay','land_area_ha','commodity','errors',
        'processed_at',
    ];

    protected $casts = [
        'is_rice'      => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(FarmerImportBatch::class, 'batch_id');
    }
}