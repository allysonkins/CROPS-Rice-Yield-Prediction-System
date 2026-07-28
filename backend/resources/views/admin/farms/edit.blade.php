@extends('layouts.app')

@section('title', 'Edit Farm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Farm</h4>
    <a href="{{ route('admin.farms.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card-custom">
    <form action="{{ route('admin.farms.update', $farm->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g., Dela Cruz Rice Field" value="{{ old('name', $farm->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                <input type="text" name="barangay" class="form-control @error('barangay') is-invalid @enderror" placeholder="e.g., Calaocan" value="{{ old('barangay', $farm->barangay) }}" required>
                @error('barangay')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Land Area (ha) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="land_area_ha" class="form-control @error('land_area_ha') is-invalid @enderror" placeholder="2.50" value="{{ old('land_area_ha', $farm->land_area_ha) }}" required>
                @error('land_area_ha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Soil Type <span class="text-danger">*</span></label>
                <select name="soil_type" class="form-select @error('soil_type') is-invalid @enderror" required>
                    <option value="">Select soil type...</option>
                    <option value="Clay Loam" {{ old('soil_type', $farm->soil_type) == 'Clay Loam' ? 'selected' : '' }}>Clay Loam</option>
                    <option value="Silty Clay" {{ old('soil_type', $farm->soil_type) == 'Silty Clay' ? 'selected' : '' }}>Silty Clay</option>
                    <option value="Sandy Loam" {{ old('soil_type', $farm->soil_type) == 'Sandy Loam' ? 'selected' : '' }}>Sandy Loam</option>
                    <option value="Clay" {{ old('soil_type', $farm->soil_type) == 'Clay' ? 'selected' : '' }}>Clay</option>
                </select>
                @error('soil_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Assigned Farmer</label>
                <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                    <option value="">Unassigned</option>
                    @foreach($farmers as $farmer)
                        <option value="{{ $farmer->id }}" {{ old('user_id', $farm->user_id) == $farmer->id ? 'selected' : '' }}>
                            {{ $farmer->name }} ({{ $farmer->email }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Coordinates</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="number" step="0.00000001" name="latitude" class="form-control @error('latitude') is-invalid @enderror" placeholder="Latitude" value="{{ old('latitude', $farm->latitude) }}">
                    </div>
                    <div class="col-6">
                        <input type="number" step="0.00000001" name="longitude" class="form-control @error('longitude') is-invalid @enderror" placeholder="Longitude" value="{{ old('longitude', $farm->longitude) }}">
                    </div>
                </div>
                <small class="text-muted">Optional — used for map visualization</small>
                @error('latitude')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @error('longitude')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save"></i> Update Farm
                </button>
                <a href="{{ route('admin.farms.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection