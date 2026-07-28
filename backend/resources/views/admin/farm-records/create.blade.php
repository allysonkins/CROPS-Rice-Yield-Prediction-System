@extends('layouts.app')

@section('title', 'Add Farm Record')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Add Farm Record</h4>
    <a href="{{ route('admin.farm-records.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card-custom">
    <form action="{{ route('admin.farm-records.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Farm <span class="text-danger">*</span></label>
                <select name="farm_id" class="form-select" required>
                    <option value="">Select a farm...</option>
                    @foreach($farms as $farm)
                        <option value="{{ $farm->id }}" {{ old('farm_id') == $farm->id ? 'selected' : '' }}>
                            {{ $farm->name }} ({{ $farm->barangay }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Rice Variety <span class="text-danger">*</span></label>
                <select name="rice_variety_id" class="form-select" required>
                    <option value="">Select a variety...</option>
                    @foreach($varieties as $variety)
                        <option value="{{ $variety->id }}" {{ old('rice_variety_id') == $variety->id ? 'selected' : '' }}>
                            {{ $variety->name }} ({{ $variety->classification }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Season <span class="text-danger">*</span></label>
                <input type="text" name="season" class="form-control" placeholder="e.g., Wet 2026" value="{{ old('season') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Fertilizer (kg/ha) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="fertilizer_kg_ha" class="form-control" placeholder="120" value="{{ old('fertilizer_kg_ha') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Historical Yield (t/ha)</label>
                <input type="number" step="0.01" name="historical_yield_tons_ha" class="form-control" placeholder="4.2" value="{{ old('historical_yield_tons_ha') }}">
                <small class="text-muted">Previous season's yield (if available)</small>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Seeding Method</label>
                <select name="seeding_method" class="form-select">
                    <option value="">Select...</option>
                    <option value="Transplanted" {{ old('seeding_method') == 'Transplanted' ? 'selected' : '' }}>Transplanted</option>
                    <option value="Direct Seeded" {{ old('seeding_method') == 'Direct Seeded' ? 'selected' : '' }}>Direct Seeded</option>
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Save Record
                </button>
                <a href="{{ route('admin.farm-records.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection