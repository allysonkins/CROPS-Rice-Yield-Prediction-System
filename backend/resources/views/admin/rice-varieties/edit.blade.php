@extends('layouts.app')

@section('title', 'Edit Rice Variety')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">✏️ Edit Rice Variety</h4>
    <a href="{{ route('admin.rice-varieties.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="card-custom">
    <form action="{{ route('admin.rice-varieties.update', $variety->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Variety Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $variety->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Classification <span class="text-danger">*</span></label>
                <select name="classification" class="form-select @error('classification') is-invalid @enderror" required>
                    <option value="Hybrid" {{ old('classification', $variety->classification) == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                    <option value="Inbred" {{ old('classification', $variety->classification) == 'Inbred' ? 'selected' : '' }}>Inbred</option>
                </select>
                @error('classification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Growth Period (Days) <span class="text-danger">*</span></label>
                <input type="number" name="growth_period" class="form-control @error('growth_period') is-invalid @enderror" value="{{ old('growth_period', $variety->growth_period) }}" required>
                @error('growth_period')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Disease Susceptibility</label>
                <input type="text" name="disease_susceptibility" class="form-control @error('disease_susceptibility') is-invalid @enderror" value="{{ old('disease_susceptibility', $variety->disease_susceptibility) }}">
                @error('disease_susceptibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Optimal Temp (Min °C)</label>
                <input type="number" step="0.1" name="optimal_temp_min" class="form-control @error('optimal_temp_min') is-invalid @enderror" value="{{ old('optimal_temp_min', $variety->optimal_temp_min) }}">
                @error('optimal_temp_min')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Optimal Temp (Max °C)</label>
                <input type="number" step="0.1" name="optimal_temp_max" class="form-control @error('optimal_temp_max') is-invalid @enderror" value="{{ old('optimal_temp_max', $variety->optimal_temp_max) }}">
                @error('optimal_temp_max')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-warning px-4">
                    <i class="bi bi-save"></i> Update Variety
                </button>
                <a href="{{ route('admin.rice-varieties.index') }}" class="btn btn-secondary px-4">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection