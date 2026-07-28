@extends('layouts.app')

@section('title', 'Manage Farms')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-x-circle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-end align-items-center mb-4">
    <a href="{{ route('admin.farms.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add Farm
    </a>
    <span class="badge bg-secondary ms-2">{{ $farms->count() }} Farms</span>
</div>

<!-- Stats Cards -->
<div class="row g-2 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Farms</div>
                <div class="value">{{ $farms->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Barangays</div>
                <div class="value">{{ $farms->pluck('barangay')->unique()->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-geo"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Farmers Assigned</div>
                <div class="value">{{ $farms->whereNotNull('user_id')->count() }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="label">Total Area (ha)</div>
                <div class="value">{{ number_format($farms->sum('land_area_ha'), 1) }}</div>
            </div>
            <div class="stat-icon"><i class="bi bi-rulers"></i></div>
        </div>
    </div>
</div>

<!-- Farm List Table -->
<div class="card-custom">
    <div class="card-title"><i class="bi bi-table"></i> Farm List</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm Name</th>
                    <th>Barangay</th>
                    <th>Farmer</th>
                    <th>Area (ha)</th>
                    <th>Soil Type</th>
                    <th>Coordinates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($farms as $farm)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $farm->name }}</strong></td>
                        <td>{{ $farm->barangay }}</td>
                        <td>{{ $farm->user->name ?? 'Unassigned' }}</td>
                        <td>{{ number_format($farm->land_area_ha, 2) }}</td>
                        <td>{{ $farm->soil_type }}</td>
                        <td>
                            @if($farm->latitude && $farm->longitude)
                                <span class="badge bg-light text-muted" style="font-size: 10px;">
                                    {{ number_format($farm->latitude, 6) }}, {{ number_format($farm->longitude, 6) }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.farms.edit', $farm->id) }}" class="btn btn-sm btn-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.farms.destroy', $farm->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this farm?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farms yet.</p>
                            <a href="{{ route('admin.farms.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Add Farm
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection