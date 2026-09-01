<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advisory extends Model
{
    use HasFactory;

    protected $fillable = [
    'title',
    'content',
    'target_audience',
    'expiry_date',
    'is_active',
    'published_at',
    'user_id',
    'image',
];

    protected $casts = [
        'expiry_date' => 'date',
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}