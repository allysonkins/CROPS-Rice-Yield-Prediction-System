@extends('layouts.app')

@section('title', 'Prediction History')

@section('content')

{{-- Back link --}}
<div class="mb-3">
    <a href="{{ route('farmer.predictions.index') }}"
       class="text-decoration-none"
       style="font-size: 13px; color: var(--slate-500); font-weight: 500;">
        <i class="bi bi-arrow-left"></i> Back to My Predictions
    </a>
</div>

{{-- Header --}}
<div class="card-custom" style="border-left: 4px solid var(--brand-green);">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div style="min-width: 0;">
            <h5 style="color: var(--slate-900); font-weight: 800; margin-bottom: 4px; letter-spacing: -0.3px;">
                <i class="bi bi-clock-history" style="color: var(--brand-green);"></i>
                Prediction History
            </h5>
            <p style="color: var(--slate-500); font-size: 13px; margin: 0;">
                <strong>{{ $farmRecord->farm->name ?? 'N/A' }}</strong>
                · {{ $farmRecord->farm->barangay ?? 'N/A' }}
                · {{ $farmRecord->riceVariety->name ?? 'N/A' }}
                · {{ $farmRecord->season ?? 'N/A' }}
                · {{ $farmRecord->seeding_method ?? 'N/A' }}
            </p>
        </div>
        <span class="badge-status"
              style="background: var(--brand-green-light); color: var(--brand-green-dark); border-color: #c7e6d2;">
            <span class="dot" style="background: var(--brand-green);"></span>
            {{ $predictions->count() }} prediction{{ $predictions->count() !== 1 ? 's' : '' }}
        </span>
    </div>
</div>

{{-- The full history content --}}
@include('farmer.prediction-history')

@endsection