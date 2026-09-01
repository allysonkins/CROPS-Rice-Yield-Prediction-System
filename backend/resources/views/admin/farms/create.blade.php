<!-- ============================================================ -->
<!-- CREATE FARM (loaded into modal)                               -->
<!-- ============================================================ -->
<div id="formErrors"></div>

<form id="farmForm" action="{{ route('admin.farms.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Dela Cruz Rice Field" value="{{ old('name') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
            <input type="text" name="barangay" class="form-control" placeholder="e.g., Calaocan" value="{{ old('barangay') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Land Area (ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="land_area_ha" class="form-control" placeholder="2.50" value="{{ old('land_area_ha') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Soil Type <span class="text-danger">*</span></label>
            <select name="soil_type" class="form-select" required>
                <option value="">Select soil type...</option>
                <option value="Clay Loam" {{ old('soil_type') == 'Clay Loam' ? 'selected' : '' }}>Clay Loam</option>
                <option value="Silty Clay" {{ old('soil_type') == 'Silty Clay' ? 'selected' : '' }}>Silty Clay</option>
                <option value="Sandy Loam" {{ old('soil_type') == 'Sandy Loam' ? 'selected' : '' }}>Sandy Loam</option>
                <option value="Clay" {{ old('soil_type') == 'Clay' ? 'selected' : '' }}>Clay</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Assigned Farmer</label>
            <select name="user_id" class="form-select">
                <option value="">Unassigned</option>
                @foreach($farmers as $farmer)
                    <option value="{{ $farmer->id }}" {{ old('user_id') == $farmer->id ? 'selected' : '' }}>
                        {{ $farmer->name }} ({{ $farmer->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- ===== MAP SECTION ===== -->
        <div class="col-12">
            <label class="form-label fw-semibold">Location</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="0.00000001" name="latitude" id="latitude" class="form-control" placeholder="Latitude" value="{{ old('latitude') }}">
                </div>
                <div class="col-6">
                    <input type="number" step="0.00000001" name="longitude" id="longitude" class="form-control" placeholder="Longitude" value="{{ old('longitude') }}">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" id="clearLocation" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
            <div id="farmMap" style="height:300px; width:100%; border-radius:12px; border:1px solid #ddd; background:#e8ecf1; margin-top:8px;"></div>
            <small class="text-muted">Click on the map to set coordinates.</small>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Farm
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>