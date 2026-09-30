<div id="formErrors"></div>

<form id="farmerFarmForm" action="{{ route('farmer.farms.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   placeholder="e.g., Bahay Kubo Rice Field"
                   value="{{ old('name') }}" required>
            <small class="text-muted">Any name you use for this farm.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
            <select name="barangay" class="form-select" required>
                <option value="">Select barangay...</option>
                @foreach(config('santiago.barangays', []) as $brgy)
                    <option value="{{ $brgy }}" {{ old('barangay') == $brgy ? 'selected' : '' }}>{{ $brgy }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Land Area (hectares) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" max="100" name="land_area_ha" class="form-control"
                   placeholder="e.g., 1.50" value="{{ old('land_area_ha') }}" required>
            <small class="text-muted">1 hectare ≈ 10,000 m².</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Soil Type <span class="text-danger">*</span></label>
            <select name="soil_type" class="form-select" required>
                <option value="">Select soil type...</option>
                @foreach(['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay', 'Loam'] as $type)
                    <option value="{{ $type }}" {{ old('soil_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
            <small class="text-muted">Not sure? Pick the closest — you can change it later.</small>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold">Location</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="0.00000001" name="latitude" id="farmer_latitude"
                           class="form-control" placeholder="Latitude"
                           value="{{ old('latitude') }}">
                </div>
                <div class="col-6">
                    <input type="number" step="0.00000001" name="longitude" id="farmer_longitude"
                           class="form-control" placeholder="Longitude"
                           value="{{ old('longitude') }}">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" id="farmer_clearLocation" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
            <div id="farmerFarmModalMap"></div>
            <small class="text-muted">Click on the map to set the exact location. Optional.</small>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Farm
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>