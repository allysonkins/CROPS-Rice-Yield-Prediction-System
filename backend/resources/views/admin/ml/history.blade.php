@extends('layouts.app')

@section('title', 'Training History')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 style="font-weight: 800; color: var(--slate-900); margin: 0;">
            <i class="bi bi-clock-history" style="color: var(--brand-green);"></i> Training History
        </h5>
        <p style="font-size: 13px; color: var(--slate-500); margin: 4px 0 0;">All past model retraining runs.</p>
    </div>
    <a href="{{ route('admin.ml.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card-custom">
    @if($runs->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size: 42px;"></i>
            <p class="mt-3 mb-0">No training runs yet.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Accuracy</th>
                        <th>Samples</th>
                        <th>Duration</th>
                        <th>Triggered</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($runs as $run)
                        <tr>
                            <td>{{ $run->id }}</td>
                            <td>
                                <span class="badge-status {{ $run->status_badge_class }}">
                                    <span class="dot"></span> {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td><code style="font-size: 11px;">{{ $run->model_version ?? '—' }}</code></td>
                            <td>
                                @if($run->overall_accuracy !== null)
                                    <strong>{{ number_format($run->overall_accuracy * 100, 2) }}%</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $run->training_samples ?: '—' }}
                                @if($run->real_samples)
                                    <small class="text-muted d-block" style="font-size: 10px;">
                                        {{ $run->real_samples }} real · {{ $run->synthetic_samples }} synthetic
                                    </small>
                                @endif
                            </td>
                            <td>{{ $run->duration_seconds ? gmdate('i:s', $run->duration_seconds) : '—' }}</td>
                            <td>{{ $run->triggeredBy->name ?? 'System' }}</td>
                            <td>{{ $run->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.ml.show', $run->id) }}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $runs->links() }}</div>
    @endif
</div>

@endsection