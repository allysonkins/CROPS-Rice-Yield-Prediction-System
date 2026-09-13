@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-title"><i class="bi bi-person"></i> My Profile</div>

            {{-- ============================================================ --}}
            {{-- EMAIL VERIFICATION STATUS --}}
            {{-- ============================================================ --}}
            @if($user->email_verified_at)
                <div class="alert alert-success d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--green); border-radius: 10px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-patch-check-fill me-2" style="font-size: 20px; color: var(--green);"></i>
                        <div>
                            <strong>Email Verified</strong>
                            <div class="small text-muted">
                                Your email <strong>{{ $user->email }}</strong> was verified on
                                {{ $user->email_verified_at->format('F d, Y \a\t h:i A') }}.
                            </div>
                        </div>
                    </div>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle-fill"></i> Verified
                    </span>
                </div>
            @else
                <div class="alert alert-warning d-flex align-items-center justify-content-between" style="border-left: 4px solid var(--gold); border-radius: 10px;">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 20px; color: var(--gold);"></i>
                        <div>
                            <strong>Email Not Verified</strong>
                            <div class="small text-muted">
                                Please verify your email <strong>{{ $user->email }}</strong> to unlock all features.
                                We sent a verification link to your inbox.
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-envelope-arrow-up"></i> Resend
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- SESSION SUCCESS MESSAGE --}}
            {{-- ============================================================ --}}
            @if(session('status') === 'profile-updated')
                <div class="alert alert-success" style="border-left: 4px solid var(--green); border-radius: 10px;">
                    <i class="bi bi-check-circle-fill"></i> Profile updated successfully.
                </div>
            @endif

            @if(session('status') === 'verification-link-sent')
                <div class="alert alert-success" style="border-left: 4px solid var(--green); border-radius: 10px;">
                    <i class="bi bi-check-circle-fill"></i> A new verification link has been sent to your email.
                </div>
            @endif

            {{-- Validation errors (if any) --}}
            @if ($errors->any())
                <div class="alert alert-danger" style="border-left: 4px solid var(--red); border-radius: 10px;">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="profileForm" action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        @if($user->email_verified_at)
                            <small class="text-success">
                                <i class="bi bi-patch-check-fill"></i> Verified
                            </small>
                        @else
                            <small class="text-warning">
                                <i class="bi bi-hourglass-split"></i> Pending verification
                            </small>
                        @endif
                    </div>

                    <!-- Barangay – only for farmers -->
                    @if($user->role === 'farmer')
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">Select barangay...</option>
                                <option value="Abra" {{ $user->barangay == 'Abra' ? 'selected' : '' }}>Abra</option>
                                <option value="Ambalatungan" {{ $user->barangay == 'Ambalatungan' ? 'selected' : '' }}>Ambalatungan</option>
                                <option value="Balintocatoc" {{ $user->barangay == 'Balintocatoc' ? 'selected' : '' }}>Balintocatoc</option>
                                <option value="Baluarte" {{ $user->barangay == 'Baluarte' ? 'selected' : '' }}>Baluarte</option>
                                <option value="Bannawag Norte" {{ $user->barangay == 'Bannawag Norte' ? 'selected' : '' }}>Bannawag Norte</option>
                                <option value="Batal" {{ $user->barangay == 'Batal' ? 'selected' : '' }}>Batal</option>
                                <option value="Buenavista" {{ $user->barangay == 'Buenavista' ? 'selected' : '' }}>Buenavista</option>
                                <option value="Cabulay" {{ $user->barangay == 'Cabulay' ? 'selected' : '' }}>Cabulay</option>
                                <option value="Calao East" {{ $user->barangay == 'Calao East' ? 'selected' : '' }}>Calao East</option>
                                <option value="Calao West" {{ $user->barangay == 'Calao West' ? 'selected' : '' }}>Calao West</option>
                                <option value="Calaocan" {{ $user->barangay == 'Calaocan' ? 'selected' : '' }}>Calaocan</option>
                                <option value="Centro East" {{ $user->barangay == 'Centro East' ? 'selected' : '' }}>Centro East</option>
                                <option value="Centro West" {{ $user->barangay == 'Centro West' ? 'selected' : '' }}>Centro West</option>
                                <option value="Divisoria" {{ $user->barangay == 'Divisoria' ? 'selected' : '' }}>Divisoria</option>
                                <option value="Dubinan East" {{ $user->barangay == 'Dubinan East' ? 'selected' : '' }}>Dubinan East</option>
                                <option value="Dubinan West" {{ $user->barangay == 'Dubinan West' ? 'selected' : '' }}>Dubinan West</option>
                                <option value="Luna" {{ $user->barangay == 'Luna' ? 'selected' : '' }}>Luna</option>
                                <option value="Mabini" {{ $user->barangay == 'Mabini' ? 'selected' : '' }}>Mabini</option>
                                <option value="Malvar" {{ $user->barangay == 'Malvar' ? 'selected' : '' }}>Malvar</option>
                                <option value="Nabbuan" {{ $user->barangay == 'Nabbuan' ? 'selected' : '' }}>Nabbuan</option>
                                <option value="Naggasican" {{ $user->barangay == 'Naggasican' ? 'selected' : '' }}>Naggasican</option>
                                <option value="Patul" {{ $user->barangay == 'Patul' ? 'selected' : '' }}>Patul</option>
                                <option value="Plaridel" {{ $user->barangay == 'Plaridel' ? 'selected' : '' }}>Plaridel</option>
                                <option value="Rizal" {{ $user->barangay == 'Rizal' ? 'selected' : '' }}>Rizal</option>
                                <option value="Rosario" {{ $user->barangay == 'Rosario' ? 'selected' : '' }}>Rosario</option>
                                <option value="Sagana" {{ $user->barangay == 'Sagana' ? 'selected' : '' }}>Sagana</option>
                                <option value="Salvador" {{ $user->barangay == 'Salvador' ? 'selected' : '' }}>Salvador</option>
                                <option value="San Andres" {{ $user->barangay == 'San Andres' ? 'selected' : '' }}>San Andres</option>
                                <option value="San Isidro" {{ $user->barangay == 'San Isidro' ? 'selected' : '' }}>San Isidro</option>
                                <option value="San Jose" {{ $user->barangay == 'San Jose' ? 'selected' : '' }}>San Jose</option>
                                <option value="Santa Rosa" {{ $user->barangay == 'Santa Rosa' ? 'selected' : '' }}>Santa Rosa</option>
                                <option value="Sinili" {{ $user->barangay == 'Sinili' ? 'selected' : '' }}>Sinili</option>
                                <option value="Sinsayon" {{ $user->barangay == 'Sinsayon' ? 'selected' : '' }}>Sinsayon</option>
                                <option value="Victory Norte" {{ $user->barangay == 'Victory Norte' ? 'selected' : '' }}>Victory Norte</option>
                                <option value="Victory Sur" {{ $user->barangay == 'Victory Sur' ? 'selected' : '' }}>Victory Sur</option>
                                <option value="Villa Gonzaga" {{ $user->barangay == 'Villa Gonzaga' ? 'selected' : '' }}>Villa Gonzaga</option>
                                <option value="Villasis" {{ $user->barangay == 'Villasis' ? 'selected' : '' }}>Villasis</option>
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                        <small class="text-muted">Min 8 characters</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
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