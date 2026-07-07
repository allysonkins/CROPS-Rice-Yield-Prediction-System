@extends('layouts.app')

@section('title', 'Staff Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card-custom text-center py-5">
            <i class="bi bi-person-badge" style="font-size: 64px; color: #2e7d32;"></i>
            <h2 class="mt-3">Welcome, Staff!</h2>
            <p class="text-muted">You have access to manage Rice Varieties and Farms.</p>
            <div class="mt-4">
                <a href="/admin/rice-varieties" class="btn btn-success me-2">
                    <i class="bi bi-leaf"></i> Manage Rice Varieties
                </a>
                <a href="#" class="btn btn-outline-secondary">
                    <i class="bi bi-geo-alt"></i> Manage Farms (Coming Soon)
                </a>
            </div>
        </div>
    </div>
</div>
@endsection