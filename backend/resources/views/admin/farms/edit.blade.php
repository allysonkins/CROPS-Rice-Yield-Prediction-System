<div id="formErrors"></div>

<form id="farmForm" action="{{ route('admin.farms.update', $farm->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Farm Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $farm->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
            <input type="text" name="barangay" class="form-control" value="{{ old('barangay', $farm->barangay) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Land Area (ha) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="land_area_ha" class="form-control" value="{{ old('land_area_ha', $farm->land_area_ha) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Soil Type <span class="text-danger">*</span></label>
            <select name="soil_type" class="form-select" required>
                <option value="">Select soil type...</option>
                @foreach(['Clay Loam', 'Silty Clay', 'Sandy Loam', 'Clay'] as $type)
                    <option value="{{ $type }}" {{ old('soil_type', $farm->soil_type) == $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Assigned Farmer</label>
            <select name="user_id" class="form-select">
                <option value="">Unassigned</option>
                @foreach($farmers as $farmer)
                    <option value="{{ $farmer->id }}" {{ old('user_id', $farm->user_id) == $farmer->id ? 'selected' : '' }}>
                        {{ $farmer->name }} ({{ $farmer->email }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- ===== MAP SECTION (unique ID) ===== -->
        <div class="col-12">
            <label class="form-label fw-semibold">Location</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="number" step="0.00000001" name="latitude" id="latitude" class="form-control" value="{{ old('latitude', $farm->latitude) }}">
                </div>
                <div class="col-6">
                    <input type="number" step="0.00000001" name="longitude" id="longitude" class="form-control" value="{{ old('longitude', $farm->longitude) }}">
                </div>
            </div>
            <div class="mt-2">
                <button type="button" id="clearLocation" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
            </div>
            <div id="farmModalMap" style="height:300px; width:100%; border-radius:12px; border:1px solid #ddd; background:#e8ecf1; margin-top:8px;"></div>
            <small class="text-muted">Click on the map to set coordinates.</small>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Farm
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>