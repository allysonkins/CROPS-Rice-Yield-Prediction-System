<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmerImportBatch extends Model
{
    protected $fillable = [
        'uuid','original_filename','stored_path',
        'total_rows','new_count','duplicate_count','invalid_count','skipped_count',
        'status','error_message',
    ];

    public function rows()
    {
        return $this->hasMany(FarmerImportRow::class, 'batch_id');
    }
}