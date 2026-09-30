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
            <label class="form-label fw-semibold">Barangay</label>
            <select name="barangay" class="form-select">
                <option value="">Select barangay...</option>
                <option value="Abra" {{ old('barangay') == 'Abra' ? 'selected' : '' }}>Abra</option>
                <option value="Ambalatungan" {{ old('barangay') == 'Ambalatungan' ? 'selected' : '' }}>Ambalatungan</option>
                <option value="Balintocatoc" {{ old('barangay') == 'Balintocatoc' ? 'selected' : '' }}>Balintocatoc</option>
                <option value="Baluarte" {{ old('barangay') == 'Baluarte' ? 'selected' : '' }}>Baluarte</option>
                <option value="Bannawag Norte" {{ old('barangay') == 'Bannawag Norte' ? 'selected' : '' }}>Bannawag Norte</option>
                <option value="Batal" {{ old('barangay') == 'Batal' ? 'selected' : '' }}>Batal</option>
                <option value="Buenavista" {{ old('barangay') == 'Buenavista' ? 'selected' : '' }}>Buenavista</option>
                <option value="Cabulay" {{ old('barangay') == 'Cabulay' ? 'selected' : '' }}>Cabulay</option>
                <option value="Calao East" {{ old('barangay') == 'Calao East' ? 'selected' : '' }}>Calao East</option>
                <option value="Calao West" {{ old('barangay') == 'Calao West' ? 'selected' : '' }}>Calao West</option>
                <option value="Calaocan" {{ old('barangay') == 'Calaocan' ? 'selected' : '' }}>Calaocan</option>
                <option value="Centro East" {{ old('barangay') == 'Centro East' ? 'selected' : '' }}>Centro East</option>
                <option value="Centro West" {{ old('barangay') == 'Centro West' ? 'selected' : '' }}>Centro West</option>
                <option value="Divisoria" {{ old('barangay') == 'Divisoria' ? 'selected' : '' }}>Divisoria</option>
                <option value="Dubinan East" {{ old('barangay') == 'Dubinan East' ? 'selected' : '' }}>Dubinan East</option>
                <option value="Dubinan West" {{ old('barangay') == 'Dubinan West' ? 'selected' : '' }}>Dubinan West</option>
                <option value="Luna" {{ old('barangay') == 'Luna' ? 'selected' : '' }}>Luna</option>
                <option value="Mabini" {{ old('barangay') == 'Mabini' ? 'selected' : '' }}>Mabini</option>
                <option value="Malvar" {{ old('barangay') == 'Malvar' ? 'selected' : '' }}>Malvar</option>
                <option value="Nabbuan" {{ old('barangay') == 'Nabbuan' ? 'selected' : '' }}>Nabbuan</option>
                <option value="Naggasican" {{ old('barangay') == 'Naggasican' ? 'selected' : '' }}>Naggasican</option>
                <option value="Patul" {{ old('barangay') == 'Patul' ? 'selected' : '' }}>Patul</option>
                <option value="Plaridel" {{ old('barangay') == 'Plaridel' ? 'selected' : '' }}>Plaridel</option>
                <option value="Rizal" {{ old('barangay') == 'Rizal' ? 'selected' : '' }}>Rizal</option>
                <option value="Rosario" {{ old('barangay') == 'Rosario' ? 'selected' : '' }}>Rosario</option>
                <option value="Sagana" {{ old('barangay') == 'Sagana' ? 'selected' : '' }}>Sagana</option>
                <option value="Salvador" {{ old('barangay') == 'Salvador' ? 'selected' : '' }}>Salvador</option>
                <option value="San Andres" {{ old('barangay') == 'San Andres' ? 'selected' : '' }}>San Andres</option>
                <option value="San Isidro" {{ old('barangay') == 'San Isidro' ? 'selected' : '' }}>San Isidro</option>
                <option value="San Jose" {{ old('barangay') == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                <option value="Santa Rosa" {{ old('barangay') == 'Santa Rosa' ? 'selected' : '' }}>Santa Rosa</option>
                <option value="Sinili" {{ old('barangay') == 'Sinili' ? 'selected' : '' }}>Sinili</option>
                <option value="Sinsayon" {{ old('barangay') == 'Sinsayon' ? 'selected' : '' }}>Sinsayon</option>
                <option value="Victory Norte" {{ old('barangay') == 'Victory Norte' ? 'selected' : '' }}>Victory Norte</option>
                <option value="Victory Sur" {{ old('barangay') == 'Victory Sur' ? 'selected' : '' }}>Victory Sur</option>
                <option value="Villa Gonzaga" {{ old('barangay') == 'Villa Gonzaga' ? 'selected' : '' }}>Villa Gonzaga</option>
                <option value="Villasis" {{ old('barangay') == 'Villasis' ? 'selected' : '' }}>Villasis</option>
            </select>
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

        <!-- ===== MAP SECTION (unique ID) ===== -->
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
            <div id="farmModalMap" style="height:300px; width:100%; border-radius:12px; border:1px solid #ddd; background:#e8ecf1; margin-top:8px;"></div>
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