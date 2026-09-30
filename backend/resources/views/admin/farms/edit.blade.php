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
            <label class="form-label fw-semibold">Barangay</label>
            <select name="barangay" class="form-select">
                <option value="">Select barangay...</option>
                <option value="Abra" {{ old('barangay', $farm->barangay) == 'Abra' ? 'selected' : '' }}>Abra</option>
                <option value="Ambalatungan" {{ old('barangay', $farm->barangay) == 'Ambalatungan' ? 'selected' : '' }}>Ambalatungan</option>
                <option value="Balintocatoc" {{ old('barangay', $farm->barangay) == 'Balintocatoc' ? 'selected' : '' }}>Balintocatoc</option>
                <option value="Baluarte" {{ old('barangay', $farm->barangay) == 'Baluarte' ? 'selected' : '' }}>Baluarte</option>
                <option value="Bannawag Norte" {{ old('barangay', $farm->barangay) == 'Bannawag Norte' ? 'selected' : '' }}>Bannawag Norte</option>
                <option value="Batal" {{ old('barangay', $farm->barangay) == 'Batal' ? 'selected' : '' }}>Batal</option>
                <option value="Buenavista" {{ old('barangay', $farm->barangay) == 'Buenavista' ? 'selected' : '' }}>Buenavista</option>
                <option value="Cabulay" {{ old('barangay', $farm->barangay) == 'Cabulay' ? 'selected' : '' }}>Cabulay</option>
                <option value="Calao East" {{ old('barangay', $farm->barangay) == 'Calao East' ? 'selected' : '' }}>Calao East</option>
                <option value="Calao West" {{ old('barangay', $farm->barangay) == 'Calao West' ? 'selected' : '' }}>Calao West</option>
                <option value="Calaocan" {{ old('barangay', $farm->barangay) == 'Calaocan' ? 'selected' : '' }}>Calaocan</option>
                <option value="Centro East" {{ old('barangay', $farm->barangay) == 'Centro East' ? 'selected' : '' }}>Centro East</option>
                <option value="Centro West" {{ old('barangay', $farm->barangay) == 'Centro West' ? 'selected' : '' }}>Centro West</option>
                <option value="Divisoria" {{ old('barangay', $farm->barangay) == 'Divisoria' ? 'selected' : '' }}>Divisoria</option>
                <option value="Dubinan East" {{ old('barangay', $farm->barangay) == 'Dubinan East' ? 'selected' : '' }}>Dubinan East</option>
                <option value="Dubinan West" {{ old('barangay', $farm->barangay) == 'Dubinan West' ? 'selected' : '' }}>Dubinan West</option>
                <option value="Luna" {{ old('barangay', $farm->barangay) == 'Luna' ? 'selected' : '' }}>Luna</option>
                <option value="Mabini" {{ old('barangay', $farm->barangay) == 'Mabini' ? 'selected' : '' }}>Mabini</option>
                <option value="Malvar" {{ old('barangay', $farm->barangay) == 'Malvar' ? 'selected' : '' }}>Malvar</option>
                <option value="Nabbuan" {{ old('barangay', $farm->barangay) == 'Nabbuan' ? 'selected' : '' }}>Nabbuan</option>
                <option value="Naggasican" {{ old('barangay', $farm->barangay) == 'Naggasican' ? 'selected' : '' }}>Naggasican</option>
                <option value="Patul" {{ old('barangay', $farm->barangay) == 'Patul' ? 'selected' : '' }}>Patul</option>
                <option value="Plaridel" {{ old('barangay', $farm->barangay) == 'Plaridel' ? 'selected' : '' }}>Plaridel</option>
                <option value="Rizal" {{ old('barangay', $farm->barangay) == 'Rizal' ? 'selected' : '' }}>Rizal</option>
                <option value="Rosario" {{ old('barangay', $farm->barangay) == 'Rosario' ? 'selected' : '' }}>Rosario</option>
                <option value="Sagana" {{ old('barangay', $farm->barangay) == 'Sagana' ? 'selected' : '' }}>Sagana</option>
                <option value="Salvador" {{ old('barangay', $farm->barangay) == 'Salvador' ? 'selected' : '' }}>Salvador</option>
                <option value="San Andres" {{ old('barangay', $farm->barangay) == 'San Andres' ? 'selected' : '' }}>San Andres</option>
                <option value="San Isidro" {{ old('barangay', $farm->barangay) == 'San Isidro' ? 'selected' : '' }}>San Isidro</option>
                <option value="San Jose" {{ old('barangay', $farm->barangay) == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                <option value="Santa Rosa" {{ old('barangay', $farm->barangay) == 'Santa Rosa' ? 'selected' : '' }}>Santa Rosa</option>
                <option value="Sinili" {{ old('barangay', $farm->barangay) == 'Sinili' ? 'selected' : '' }}>Sinili</option>
                <option value="Sinsayon" {{ old('barangay', $farm->barangay) == 'Sinsayon' ? 'selected' : '' }}>Sinsayon</option>
                <option value="Victory Norte" {{ old('barangay', $farm->barangay) == 'Victory Norte' ? 'selected' : '' }}>Victory Norte</option>
                <option value="Victory Sur" {{ old('barangay', $farm->barangay) == 'Victory Sur' ? 'selected' : '' }}>Victory Sur</option>
                <option value="Villa Gonzaga" {{ old('barangay', $farm->barangay) == 'Villa Gonzaga' ? 'selected' : '' }}>Villa Gonzaga</option>
                <option value="Villasis" {{ old('barangay', $farm->barangay) == 'Villasis' ? 'selected' : '' }}>Villasis</option>
            </select>
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