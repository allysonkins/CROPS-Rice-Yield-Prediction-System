@extends('layouts.app')

@section('title', 'Training Run #' . $run->id)

@section('content')

<div class="mb-3">
    <a href="{{ route('admin.ml.index') }}" class="text-decoration-none"
       style="font-size: 13px; color: var(--slate-500);">
        <i class="bi bi-arrow-left"></i> Back to Model Training
    </a>
</div>

<div class="card-custom mb-3" style="border-top: 4px solid var(--brand-green);">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 style="margin: 0; font-weight: 800; color: var(--slate-900);">
            Run #{{ $run->id }} · {{ $run->model_version ?? 'untitled' }}
        </h5>
        <span class="badge-status {{ $run->status_badge_class }}">
            <i class="bi bi-{{ $run->status_icon }}"></i> {{ ucfirst($run->status) }}
        </span>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-6 col-md-3">
            <div class="text-muted small">Accuracy</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--brand-green);">
                {{ $run->overall_accuracy !== null ? number_format($run->overall_accuracy * 100, 2) . '%' : '—' }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted small">Train / Test</div>
            <div style="font-size: 15px; font-weight: 700;">
                {{ $run->training_samples }} / {{ $run->test_samples }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted small">Real / Synthetic</div>
            <div style="font-size: 15px; font-weight: 700;">
                {{ $run->real_samples }} / {{ $run->synthetic_samples }}
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="text-muted small">Duration</div>
            <div style="font-size: 15px; font-weight: 700;">
                {{ $run->duration_seconds ? gmdate('i:s', $run->duration_seconds) : '—' }}
            </div>
        </div>
    </div>

    @if($run->error_message)
        <div class="alert-custom alert-custom-danger mt-3 mb-0">
            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
            <div class="alert-content">{{ $run->error_message }}</div>
        </div>
    @endif
</div>

@if($run->metrics)
    @php
        $m = $run->metrics;
        $fi = $m['feature_importance'] ?? [];
        arsort($fi);
        $fi = array_slice($fi, 0, 12, true);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="card-custom mb-0">
                <div class="card-title"><i class="bi bi-bar-chart-fill"></i> Per-Class Scores</div>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th class="text-end">Precision</th>
                            <th class="text-end">Recall</th>
                            <th class="text-end">F1</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($m['classes'] ?? []) as $cls)
                            <tr>
                                <td><strong>{{ $cls }}</strong></td>
                                <td class="text-end">{{ number_format(($m['precision_per_class'][$cls] ?? 0) * 100, 1) }}%</td>
                                <td class="text-end">{{ number_format(($m['recall_per_class'][$cls] ?? 0) * 100, 1) }}%</td>
                                <td class="text-end">{{ number_format(($m['f1_per_class'][$cls] ?? 0) * 100, 1) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card-custom mb-0">
                <div class="card-title"><i class="bi bi-diagram-3-fill"></i> Top Feature Importance</div>
                @forelse($fi as $feat => $val)
                    <div style="margin-bottom: 8px;">
                        <div class="d-flex justify-content-between" style="font-size: 12px;">
                            <span style="font-family: monospace;">{{ $feat }}</span>
                            <strong>{{ number_format($val * 100, 2) }}%</strong>
                        </div>
                        <div style="height: 6px; background: var(--slate-100); border-radius: 99px; margin-top: 2px;">
                            <div style="height: 6px; width: {{ min(100, $val * 100 * 3) }}%; background: var(--brand-green); border-radius: 99px;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0" style="font-size: 13px;">No feature importance data.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif

<div class="card-custom">
    <div class="card-title"><i class="bi bi-terminal-fill"></i> Training Log</div>
    <pre style="padding: 14px; background: var(--slate-900); color: #d1fae5; border-radius: 8px; font-size: 11px; max-height: 400px; overflow: auto; white-space: pre-wrap; margin: 0;">{{ $run->log_output ?? 'No log captured.' }}</pre>
</div>

@endsection