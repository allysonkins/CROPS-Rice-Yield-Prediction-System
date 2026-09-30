<div id="formErrors"></div>

<form id="farmerForm" action="{{ route('admin.farmers.update', $farmer->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $farmer->name) }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">RSBSA Number</label>
            <input type="text" name="rsbsa_number" class="form-control" placeholder="e.g., RSBSA-0001" value="{{ old('rsbsa_number', $farmer->rsbsa_number) }}">
            <small class="text-muted">Optional.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Phone Number</label>
            <input type="text" name="phone" class="form-control" placeholder="09171234567" value="{{ old('phone', $farmer->phone) }}" pattern="09[0-9]{9}" maxlength="11">
            <small class="text-muted">PH mobile — used for login.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email <span class="text-muted">(optional)</span></label>
            @php
                $isPlaceholder = $farmer->email && \Illuminate\Support\Str::endsWith($farmer->email, '@crops.local');
            @endphp
            <input type="email" name="email" class="form-control"
                   placeholder="{{ $isPlaceholder ? 'Not set — no email on file' : '' }}"
                   value="{{ old('email', $isPlaceholder ? '' : $farmer->email) }}">
            @if($isPlaceholder)
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    This farmer has no email. Leave blank to keep it that way.
                </small>
            @else
                <small class="text-muted">Leave blank to keep current.</small>
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
            <small class="text-muted">Min 8 characters.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Barangay</label>
            <select name="barangay" class="form-select">
                <option value="">Select barangay...</option>
                <option value="Abra" {{ old('barangay', $farmer->barangay) == 'Abra' ? 'selected' : '' }}>Abra</option>
                <option value="Ambalatungan" {{ old('barangay', $farmer->barangay) == 'Ambalatungan' ? 'selected' : '' }}>Ambalatungan</option>
                <option value="Balintocatoc" {{ old('barangay', $farmer->barangay) == 'Balintocatoc' ? 'selected' : '' }}>Balintocatoc</option>
                <option value="Baluarte" {{ old('barangay', $farmer->barangay) == 'Baluarte' ? 'selected' : '' }}>Baluarte</option>
                <option value="Bannawag Norte" {{ old('barangay', $farmer->barangay) == 'Bannawag Norte' ? 'selected' : '' }}>Bannawag Norte</option>
                <option value="Batal" {{ old('barangay', $farmer->barangay) == 'Batal' ? 'selected' : '' }}>Batal</option>
                <option value="Buenavista" {{ old('barangay', $farmer->barangay) == 'Buenavista' ? 'selected' : '' }}>Buenavista</option>
                <option value="Cabulay" {{ old('barangay', $farmer->barangay) == 'Cabulay' ? 'selected' : '' }}>Cabulay</option>
                <option value="Calao East" {{ old('barangay', $farmer->barangay) == 'Calao East' ? 'selected' : '' }}>Calao East</option>
                <option value="Calao West" {{ old('barangay', $farmer->barangay) == 'Calao West' ? 'selected' : '' }}>Calao West</option>
                <option value="Calaocan" {{ old('barangay', $farmer->barangay) == 'Calaocan' ? 'selected' : '' }}>Calaocan</option>
                <option value="Centro East" {{ old('barangay', $farmer->barangay) == 'Centro East' ? 'selected' : '' }}>Centro East</option>
                <option value="Centro West" {{ old('barangay', $farmer->barangay) == 'Centro West' ? 'selected' : '' }}>Centro West</option>
                <option value="Divisoria" {{ old('barangay', $farmer->barangay) == 'Divisoria' ? 'selected' : '' }}>Divisoria</option>
                <option value="Dubinan East" {{ old('barangay', $farmer->barangay) == 'Dubinan East' ? 'selected' : '' }}>Dubinan East</option>
                <option value="Dubinan West" {{ old('barangay', $farmer->barangay) == 'Dubinan West' ? 'selected' : '' }}>Dubinan West</option>
                <option value="Luna" {{ old('barangay', $farmer->barangay) == 'Luna' ? 'selected' : '' }}>Luna</option>
                <option value="Mabini" {{ old('barangay', $farmer->barangay) == 'Mabini' ? 'selected' : '' }}>Mabini</option>
                <option value="Malvar" {{ old('barangay', $farmer->barangay) == 'Malvar' ? 'selected' : '' }}>Malvar</option>
                <option value="Nabbuan" {{ old('barangay', $farmer->barangay) == 'Nabbuan' ? 'selected' : '' }}>Nabbuan</option>
                <option value="Naggasican" {{ old('barangay', $farmer->barangay) == 'Naggasican' ? 'selected' : '' }}>Naggasican</option>
                <option value="Patul" {{ old('barangay', $farmer->barangay) == 'Patul' ? 'selected' : '' }}>Patul</option>
                <option value="Plaridel" {{ old('barangay', $farmer->barangay) == 'Plaridel' ? 'selected' : '' }}>Plaridel</option>
                <option value="Rizal" {{ old('barangay', $farmer->barangay) == 'Rizal' ? 'selected' : '' }}>Rizal</option>
                <option value="Rosario" {{ old('barangay', $farmer->barangay) == 'Rosario' ? 'selected' : '' }}>Rosario</option>
                <option value="Sagana" {{ old('barangay', $farmer->barangay) == 'Sagana' ? 'selected' : '' }}>Sagana</option>
                <option value="Salvador" {{ old('barangay', $farmer->barangay) == 'Salvador' ? 'selected' : '' }}>Salvador</option>
                <option value="San Andres" {{ old('barangay', $farmer->barangay) == 'San Andres' ? 'selected' : '' }}>San Andres</option>
                <option value="San Isidro" {{ old('barangay', $farmer->barangay) == 'San Isidro' ? 'selected' : '' }}>San Isidro</option>
                <option value="San Jose" {{ old('barangay', $farmer->barangay) == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                <option value="Santa Rosa" {{ old('barangay', $farmer->barangay) == 'Santa Rosa' ? 'selected' : '' }}>Santa Rosa</option>
                <option value="Sinili" {{ old('barangay', $farmer->barangay) == 'Sinili' ? 'selected' : '' }}>Sinili</option>
                <option value="Sinsayon" {{ old('barangay', $farmer->barangay) == 'Sinsayon' ? 'selected' : '' }}>Sinsayon</option>
                <option value="Victory Norte" {{ old('barangay', $farmer->barangay) == 'Victory Norte' ? 'selected' : '' }}>Victory Norte</option>
                <option value="Victory Sur" {{ old('barangay', $farmer->barangay) == 'Victory Sur' ? 'selected' : '' }}>Victory Sur</option>
                <option value="Villa Gonzaga" {{ old('barangay', $farmer->barangay) == 'Villa Gonzaga' ? 'selected' : '' }}>Villa Gonzaga</option>
                <option value="Villasis" {{ old('barangay', $farmer->barangay) == 'Villasis' ? 'selected' : '' }}>Villasis</option>
            </select>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Update Farmer
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>