@extends('layouts.app')

@section('title', 'My Profile')

@php
    $user       = auth()->user();
    $isFarmer   = $user->role === 'farmer';
    $isVerified = $user->verified_by_cao_at !== null;
@endphp

@section('content')

<div class="row g-3">

    {{-- ═══════════ STATUS BANNER (farmer: CAO verification | staff: email verification) ═══════════ --}}
    <div class="col-12">
        @if($isFarmer)
            @if($isVerified)
                <div class="alert-custom alert-custom-success mb-0">
                    <i class="bi bi-patch-check-fill alert-icon"></i>
                    <div class="alert-content">
                        <strong>CAO Verified</strong>
                        <div style="font-size: 12px; color: var(--brand-green-dark); opacity: 0.8; margin-top: 2px;">
                            Your account was verified by the City Agriculture Office
                            @if($user->verified_by_cao_at)
                                on {{ $user->verified_by_cao_at->format('F d, Y') }}
                            @endif
                            . You have full access to record farm seasons and receive yield predictions.
                        </div>
                    </div>
                </div>
            @else
                <div class="alert-custom alert-custom-warning mb-0">
                    <i class="bi bi-shield-exclamation alert-icon"></i>
                    <div class="alert-content">
                        <strong>Awaiting CAO Verification</strong>
                        <div style="font-size: 12px; color: #78350f; opacity: 0.9; margin-top: 2px;">
                            You can view your data, but recording new farms and seasons will unlock once the
                            City Agriculture Office verifies your account.
                        </div>
                    </div>
                </div>
            @endif
        @else
            @if($user->email_verified_at)
                <div class="alert-custom alert-custom-success mb-0">
                    <i class="bi bi-patch-check-fill alert-icon"></i>
                    <div class="alert-content">
                        <strong>Email Verified</strong>
                        <div style="font-size: 12px; color: var(--brand-green-dark); opacity: 0.8; margin-top: 2px;">
                            Your email <strong>{{ $user->email }}</strong> was verified on
                            {{ $user->email_verified_at->format('F d, Y \a\t h:i A') }}.
                        </div>
                    </div>
                </div>
            @else
                <div class="alert-custom alert-custom-warning mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                        <div class="alert-content">
                            <strong>Email Not Verified</strong>
                            <div style="font-size: 12px; color: #78350f; opacity: 0.9; margin-top: 2px;">
                                Verify <strong>{{ $user->email }}</strong> to unlock all features.
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-envelope-arrow-up"></i> Resend Link
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>

    {{-- ═══════════ SESSION FLASH ═══════════ --}}
    @if(session('success'))
        <div class="col-12">
            <div class="alert-custom alert-custom-success mb-0">
                <i class="bi bi-check-circle-fill alert-icon"></i>
                <div class="alert-content">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('status') === 'verification-link-sent')
        <div class="col-12">
            <div class="alert-custom alert-custom-success mb-0">
                <i class="bi bi-envelope-check-fill alert-icon"></i>
                <div class="alert-content">A new verification link has been sent to your email.</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="col-12">
            <div class="alert-custom alert-custom-danger mb-0">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div class="alert-content">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════ PROFILE CARD ═══════════ --}}
    <div class="col-12 col-lg-8">
        <div class="card-custom mb-0">
            <div class="card-title">
                <i class="bi bi-person-fill"></i>
                <span>My Profile</span>
            </div>

            <form id="profileForm" action="{{ $isFarmer ? route('farmer.profile.update') : route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">

                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $user->name) }}" required>
                    </div>

                    @if($isFarmer)

                        {{-- Phone (farmer login identifier) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control"
                                   placeholder="09171234567"
                                   pattern="09[0-9]{9}" maxlength="11"
                                   value="{{ old('phone', $user->phone) }}">
                            <small style="color: var(--slate-500);">
                                <i class="bi bi-telephone"></i>
                                This is your login. Remember this number.
                            </small>
                        </div>

                        {{-- RSBSA --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">RSBSA Number</label>
                            <input type="text" name="rsbsa_number" class="form-control"
                                   placeholder="e.g., RSBSA-0001"
                                   value="{{ old('rsbsa_number', $user->rsbsa_number) }}">
                            <small style="color: var(--slate-500);">
                                <i class="bi bi-card-checklist"></i>
                                Can also be used to log in.
                            </small>
                        </div>

                        {{-- Barangay --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Barangay</label>
                            <select name="barangay" class="form-select">
                                <option value="">Select barangay...</option>
                                @foreach(config('santiago.barangays', []) as $brgy)
                                    <option value="{{ $brgy }}" {{ old('barangay', $user->barangay) == $brgy ? 'selected' : '' }}>
                                        {{ $brgy }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    @else

                        {{-- Staff / admin email --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $user->email) }}" required>
                            @if($user->email_verified_at)
                                <small style="color: var(--brand-green);">
                                    <i class="bi bi-patch-check-fill"></i> Verified
                                </small>
                            @else
                                <small style="color: var(--brand-gold);">
                                    <i class="bi bi-hourglass-split"></i> Pending verification
                                </small>
                            @endif
                        </div>

                    @endif

                    {{-- Password / PIN --}}
                    <div class="col-12">
                        <hr style="margin: 8px 0 16px; border-color: var(--slate-200);">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            {{ $isFarmer ? 'New PIN' : 'New Password' }}
                        </label>
                        <input type="password" name="password" class="form-control"
                               placeholder="{{ $isFarmer ? 'Leave blank to keep current PIN' : 'Leave blank to keep current password' }}">
                        <small style="color: var(--slate-500);">
                            @if($isFarmer)
                                Use 6 digits that you can easily remember — this is what you'll enter with your phone number.
                            @else
                                Minimum 8 characters.
                            @endif
                        </small>
                    </div>

                    @if($isFarmer)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New PIN</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   placeholder="Re-type your new PIN">
                            <small style="color: var(--slate-500);">Must match the field above.</small>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control"
                                   placeholder="Re-type your new password">
                            <small style="color: var(--slate-500);">Must match the field above.</small>
                        </div>
                    @endif

                    {{-- Submit --}}
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="{{ $isFarmer ? route('farmer.dashboard') : route('admin.dashboard') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════ SIDE CARD — farmer login reminder ═══════════ --}}
    <div class="col-12 col-lg-4">
        @if($isFarmer)
            <div class="card-custom mb-0" style="border-left: 4px solid var(--brand-green);">
                <div class="card-title">
                    <i class="bi bi-key-fill"></i>
                    <span>How You Log In</span>
                </div>
                <p style="font-size: 13px; color: var(--slate-600); line-height: 1.6; margin-bottom: 16px;">
                    You sign in to CROPS using your <strong>phone number</strong> and <strong>PIN</strong>.
                    No email needed.
                </p>

                <div class="p-3 mb-3" style="background: var(--brand-green-light); border-radius: var(--radius-md);">
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.4px;">
                        <i class="bi bi-telephone-fill"></i> Phone
                    </div>
                    <div style="font-family: monospace; font-size: 16px; font-weight: 700; color: var(--brand-green-dark); margin-top: 2px;">
                        {{ $user->phone ?? '— not set —' }}
                    </div>
                </div>

                <div class="p-3 mb-3" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">
                        <i class="bi bi-shield-lock-fill"></i> PIN
                    </div>
                    <div style="font-size: 13px; color: var(--slate-600); margin-top: 4px; line-height: 1.5;">
                        Your PIN is private. If you've forgotten it, contact the City Agriculture Office
                        to generate a new one.
                    </div>
                </div>

                @if($user->rsbsa_number)
                    <div class="p-3 mb-0" style="background: var(--slate-50); border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-500); font-weight: 700; letter-spacing: 0.4px;">
                            <i class="bi bi-card-checklist"></i> RSBSA Number
                        </div>
                        <div style="font-family: monospace; font-size: 14px; font-weight: 600; color: var(--slate-700); margin-top: 2px;">
                            {{ $user->rsbsa_number }}
                        </div>
                        <div style="font-size: 11px; color: var(--slate-500); margin-top: 4px;">
                            You can also log in with this instead of your phone.
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="card-custom mb-0" style="border-left: 4px solid var(--brand-green);">
                <div class="card-title">
                    <i class="bi bi-shield-check"></i>
                    <span>Account</span>
                </div>
                <div class="p-3 mb-3" style="background: var(--brand-green-light); border-radius: var(--radius-md);">
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--brand-green-dark); font-weight: 700; letter-spacing: 0.4px;">
                        Role
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: var(--brand-green-dark); margin-top: 2px; text-transform: capitalize;">
                        {{ $user->role }}
                    </div>
                </div>
                <p style="font-size: 12px; color: var(--slate-500); margin: 0;">
                    You log in with your email and password.
                </p>
            </div>
        @endif
    </div>

</div>

@endsection