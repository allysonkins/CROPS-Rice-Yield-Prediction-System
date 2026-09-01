<div id="formErrors"></div>

<form id="farmerForm" action="{{ route('admin.farmers.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Juan Dela Cruz" value="{{ old('name') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="farmer@example.com" value="{{ old('email') }}" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
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

        <div class="col-12">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> Save Farmer
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</form>