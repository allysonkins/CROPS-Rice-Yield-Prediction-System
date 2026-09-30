<div id="formErrors"></div>

<form id="farmerFarmForm" action="{{ route('farmer.farms.update', $farm->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="{{ old('name', $farm->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
            <select name="barangay" class="form-select" required>
                @foreach(config('santiago.barangays', []) as $brgy)
                    <option value="{{ $brgy }}" {{ old('barangay', $farm->barangay) == $brgy ? 'selected' : '' }}>
                        {{ $brgy }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Land Area (hectares) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" max="100" name="land_area_ha" class="form-control"
                   value="{{ old('land_area_ha', $farm->land_area_ha) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Soil Type <span class="text-danger">*</span></label>
            <select name="soil_type" class="form-select" required>
                @foreach(['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay', 'Loam'] as $type)
                    <option value="{{ $type }}" {{ old('soil_type', $farm->soil_type) == $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ═════ MAP SECTION ═════ --}}
        <div class="col-12">
            <label class="form-label fw-semibold">Location</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="0.00000001" name="latitude" id="farmer_latitude"
                           class="form-control" placeholder="Latitude"
                           value="{{ old('latitude', $farm->latitude) }}">
                </div>
                <div class="col-6">
                    <input type="number" step="0.00000001" name="longitude" id="farmer_longitude"
                           class="form-control" placeholder="Longitude"
                           value="{{ old('longitude', $farm->longitude) }}">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" id="farmer_clearLocation" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
            <div id="farmerFarmModalMap"></div>
            <small class="text-muted">Click on the map to set the exact location.</small>
        </div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Farm
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>