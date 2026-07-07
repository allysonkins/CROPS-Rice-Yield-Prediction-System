@extends('layouts.app')

@section('title', 'Farmer Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card-custom text-center py-5">
            <i class="bi bi-person" style="font-size: 64px; color: #f59e0b;"></i>
            <h2 class="mt-3">Welcome, Farmer!</h2>
            <p class="text-muted">View your farm data, predictions, and advisories here.</p>
            <div class="mt-4">
                <a href="#" class="btn btn-warning me-2">
                    <i class="bi bi-graph-up"></i> View My Predictions (Coming Soon)
                </a>
                <a href="#" class="btn btn-outline-secondary">
                    <i class="bi bi-map"></i> View Farm Map (Coming Soon)
                </a>
            </div>
        </div>
    </div>
</div>
@endsection