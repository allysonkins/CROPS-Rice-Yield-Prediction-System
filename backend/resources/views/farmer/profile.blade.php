@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-person"></i> My Profile</div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form id="profileForm" action="{{ route('farmer.profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Barangay</label>
                        <select name="barangay" class="form-select">
                            <option value="">Select barangay...</option>
                            <option value="Abra" {{ auth()->user()->barangay == 'Abra' ? 'selected' : '' }}>Abra</option>
                            <option value="Ambalatungan" {{ auth()->user()->barangay == 'Ambalatungan' ? 'selected' : '' }}>Ambalatungan</option>
                            <option value="Balintocatoc" {{ auth()->user()->barangay == 'Balintocatoc' ? 'selected' : '' }}>Balintocatoc</option>
                            <option value="Baluarte" {{ auth()->user()->barangay == 'Baluarte' ? 'selected' : '' }}>Baluarte</option>
                            <option value="Bannawag Norte" {{ auth()->user()->barangay == 'Bannawag Norte' ? 'selected' : '' }}>Bannawag Norte</option>
                            <option value="Batal" {{ auth()->user()->barangay == 'Batal' ? 'selected' : '' }}>Batal</option>
                            <option value="Buenavista" {{ auth()->user()->barangay == 'Buenavista' ? 'selected' : '' }}>Buenavista</option>
                            <option value="Cabulay" {{ auth()->user()->barangay == 'Cabulay' ? 'selected' : '' }}>Cabulay</option>
                            <option value="Calao East" {{ auth()->user()->barangay == 'Calao East' ? 'selected' : '' }}>Calao East</option>
                            <option value="Calao West" {{ auth()->user()->barangay == 'Calao West' ? 'selected' : '' }}>Calao West</option>
                            <option value="Calaocan" {{ auth()->user()->barangay == 'Calaocan' ? 'selected' : '' }}>Calaocan</option>
                            <option value="Centro East" {{ auth()->user()->barangay == 'Centro East' ? 'selected' : '' }}>Centro East</option>
                            <option value="Centro West" {{ auth()->user()->barangay == 'Centro West' ? 'selected' : '' }}>Centro West</option>
                            <option value="Divisoria" {{ auth()->user()->barangay == 'Divisoria' ? 'selected' : '' }}>Divisoria</option>
                            <option value="Dubinan East" {{ auth()->user()->barangay == 'Dubinan East' ? 'selected' : '' }}>Dubinan East</option>
                            <option value="Dubinan West" {{ auth()->user()->barangay == 'Dubinan West' ? 'selected' : '' }}>Dubinan West</option>
                            <option value="Luna" {{ auth()->user()->barangay == 'Luna' ? 'selected' : '' }}>Luna</option>
                            <option value="Mabini" {{ auth()->user()->barangay == 'Mabini' ? 'selected' : '' }}>Mabini</option>
                            <option value="Malvar" {{ auth()->user()->barangay == 'Malvar' ? 'selected' : '' }}>Malvar</option>
                            <option value="Nabbuan" {{ auth()->user()->barangay == 'Nabbuan' ? 'selected' : '' }}>Nabbuan</option>
                            <option value="Naggasican" {{ auth()->user()->barangay == 'Naggasican' ? 'selected' : '' }}>Naggasican</option>
                            <option value="Patul" {{ auth()->user()->barangay == 'Patul' ? 'selected' : '' }}>Patul</option>
                            <option value="Plaridel" {{ auth()->user()->barangay == 'Plaridel' ? 'selected' : '' }}>Plaridel</option>
                            <option value="Rizal" {{ auth()->user()->barangay == 'Rizal' ? 'selected' : '' }}>Rizal</option>
                            <option value="Rosario" {{ auth()->user()->barangay == 'Rosario' ? 'selected' : '' }}>Rosario</option>
                            <option value="Sagana" {{ auth()->user()->barangay == 'Sagana' ? 'selected' : '' }}>Sagana</option>
                            <option value="Salvador" {{ auth()->user()->barangay == 'Salvador' ? 'selected' : '' }}>Salvador</option>
                            <option value="San Andres" {{ auth()->user()->barangay == 'San Andres' ? 'selected' : '' }}>San Andres</option>
                            <option value="San Isidro" {{ auth()->user()->barangay == 'San Isidro' ? 'selected' : '' }}>San Isidro</option>
                            <option value="San Jose" {{ auth()->user()->barangay == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                            <option value="Santa Rosa" {{ auth()->user()->barangay == 'Santa Rosa' ? 'selected' : '' }}>Santa Rosa</option>
                            <option value="Sinili" {{ auth()->user()->barangay == 'Sinili' ? 'selected' : '' }}>Sinili</option>
                            <option value="Sinsayon" {{ auth()->user()->barangay == 'Sinsayon' ? 'selected' : '' }}>Sinsayon</option>
                            <option value="Victory Norte" {{ auth()->user()->barangay == 'Victory Norte' ? 'selected' : '' }}>Victory Norte</option>
                            <option value="Victory Sur" {{ auth()->user()->barangay == 'Victory Sur' ? 'selected' : '' }}>Victory Sur</option>
                            <option value="Villa Gonzaga" {{ auth()->user()->barangay == 'Villa Gonzaga' ? 'selected' : '' }}>Villa Gonzaga</option>
                            <option value="Villasis" {{ auth()->user()->barangay == 'Villasis' ? 'selected' : '' }}>Villasis</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                        <small class="text-muted">Min 8 characters</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Update Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection