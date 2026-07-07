@extends('layouts.app')

@section('title', 'Rice Varieties')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Rice Varieties</h4>
    <a href="{{ route('admin.rice-varieties.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add New Variety
    </a>
</div>

<!-- Flash Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Variety Name</th>
                    <th>Classification</th>
                    <th>Growth Period (Days)</th>
                    <th>Disease Susceptibility</th>
                    <th>Optimal Temp (°C)</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($varieties as $variety)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $variety->name }}</strong></td>
                        <td>
                            @if($variety->classification == 'Hybrid')
                                <span class="badge bg-success">{{ $variety->classification }}</span>
                            @else
                                <span class="badge bg-info">{{ $variety->classification }}</span>
                            @endif
                        </td>
                        <td>{{ $variety->growth_period }}</td>
                        <td>{{ $variety->disease_susceptibility ?? 'N/A' }}</td>
                        <td>{{ $variety->optimal_temp_min ?? '-' }} - {{ $variety->optimal_temp_max ?? '-' }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.rice-varieties.edit', $variety->id) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.rice-varieties.destroy', $variety->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this variety?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 32px;"></i>
                            <p class="mt-2">No rice varieties found. Click "Add New Variety" to get started!</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection