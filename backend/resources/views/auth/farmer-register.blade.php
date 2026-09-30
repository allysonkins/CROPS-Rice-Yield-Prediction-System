@extends('layouts.auth')
@section('title', 'Farmer Registration')
@section('card_class', 'wide')

@section('content')
    <div class="brand">
        <h1>Farmer Registration</h1>
        <div class="accent"></div>
        <p>Register to access rice yield predictions for your farm</p>
    </div>

    @if ($errors->any())
        <div class="alert-custom">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
        </div>
    @endif

    <div class="alert-info-custom">
        <i class="bi bi-info-circle-fill me-2"></i>
        Your account will be <strong>verified by the CAO</strong> after registration.
        You can log in and use the system while waiting.
    </div>

    <form method="POST" action="{{ route('farmer.register.submit') }}" data-loading="Creating account...">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control"
                   value="{{ old('name') }}" placeholder="e.g., Juan Dela Cruz" required>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                <input type="text" id="phone" name="phone" class="form-control"
                       value="{{ old('phone') }}" placeholder="09171234567"
                       pattern="09[0-9]{9}" maxlength="11" required>
                <div class="helper">Your phone is your login. Keep it safe.</div>
            </div>
            <div class="col-md-6">
                <label for="rsbsa_number" class="form-label">RSBSA Number</label>
                <input type="text" id="rsbsa_number" name="rsbsa_number" class="form-control"
                       value="{{ old('rsbsa_number') }}" placeholder="e.g., RSBSA-0001">
                <div class="helper">Optional, but speeds up verification.</div>
            </div>
        </div>

        <div class="mb-3 mt-3">
            <label for="barangay" class="form-label">Barangay <span class="text-danger">*</span></label>
            <select id="barangay" name="barangay" class="form-select" required>
                <option value="">Select barangay...</option>
                @foreach(config('santiago.barangays', [
                    'Abra','Ambalatungan','Balintocatoc','Baluarte','Bannawag Norte','Batal',
                    'Buenavista','Cabulay','Calao East','Calao West','Calaocan','Centro East',
                    'Centro West','Divisoria','Dubinan East','Dubinan West','Luna','Mabini',
                    'Malvar','Nabbuan','Naggasican','Patul','Plaridel','Rizal','Rosario',
                    'Sagana','Salvador','San Andres','San Isidro','San Jose','Santa Rosa',
                    'Sinili','Sinsayon','Victory Norte','Victory Sur','Villa Gonzaga','Villasis',
                ]) as $brgy)
                    <option value="{{ $brgy }}" {{ old('barangay') == $brgy ? 'selected' : '' }}>{{ $brgy }}</option>
                @endforeach
            </select>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="password" class="form-label">PIN / Password <span class="text-danger">*</span></label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="At least 6 characters" required minlength="6">
                <div class="helper">Use something easy to remember — e.g. 6 digits.</div>
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm PIN <span class="text-danger">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="form-control" placeholder="Re-type PIN" required minlength="6">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn-primary-cta js-submit">
                <i class="bi bi-person-plus-fill me-2"></i>Create Account
            </button>
        </div>
    </form>

    <div class="card-foot">
        <p>Already have an account?</p>
        <a href="{{ route('login') }}">Log in <i class="bi bi-arrow-right"></i></a>
    </div>
@endsection