<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelTrainingRun extends Model
{
    protected $fillable = [
        'status', 'triggered_by', 'model_version', 'model_path',
        'training_samples', 'test_samples', 'real_samples', 'synthetic_samples',
        'overall_accuracy', 'metrics', 'previous_metrics',
        'log_output', 'error_message',
        'started_at', 'completed_at', 'duration_seconds',
    ];

    protected $casts = [
        'metrics'          => 'array',
        'previous_metrics' => 'array',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'high',
            'running'   => 'medium',
            'failed'    => 'low',
            default     => '',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'check-circle-fill',
            'running'   => 'arrow-repeat',
            'failed'    => 'x-circle-fill',
            default     => 'clock',
        };
    }

    public function getAccuracyDeltaAttribute(): ?float
    {
        $prev = $this->previous_metrics['overall_accuracy'] ?? null;
        if ($prev === null || $this->overall_accuracy === null) return null;
        return (float) $this->overall_accuracy - (float) $prev;
    }
}