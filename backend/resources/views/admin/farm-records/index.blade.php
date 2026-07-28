@extends('layouts.app')

@section('title', 'Farm Records')

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
    <a href="{{ route('admin.farm-records.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add Farm Record
    </a>
    <span class="badge bg-secondary ms-2">{{ $farmRecords->count() }} Records</span>
</div>

<div class="card-custom">
    <div class="card-title"><i class="bi bi-clipboard-data-fill"></i> Farm Records</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Farm</th>
                    <th>Variety</th>
                    <th>Season</th>
                    <th>Fertilizer (kg/ha)</th>
                    <th>Historical Yield</th>
                    <th>Seeding Method</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($farmRecords as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $record->farm->name ?? 'N/A' }}</strong>
                            <br><small class="text-muted">{{ $record->farm->barangay ?? '' }}</small>
                        </td>
                        <td>{{ $record->riceVariety->name ?? 'N/A' }}</td>
                        <td>{{ $record->season }}</td>
                        <td>{{ number_format($record->fertilizer_kg_ha, 2) }}</td>
                        <td>{{ $record->historical_yield_tons_ha ? number_format($record->historical_yield_tons_ha, 2) : 'N/A' }}</td>
                        <td>{{ $record->seeding_method ?? 'N/A' }}</td>
                        <td>{{ $record->created_at ? $record->created_at->format('M d, Y') : 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.farm-records.edit', $record->id) }}" class="btn btn-sm btn-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.farm-records.destroy', $record->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 28px;"></i>
                            <p class="mt-2 mb-0">No farm records yet.</p>
                            <a href="{{ route('admin.farm-records.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Add Farm Record
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection