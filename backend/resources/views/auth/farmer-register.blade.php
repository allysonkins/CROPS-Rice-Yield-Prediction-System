<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CROPS — Farmer Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <style>
        :root {
            --green: #1b5e3a; --green-dark: #0d3d24; --green-light: #eaf7ee;
            --yellow: #f2b705; --red: #c0392b; --red-light: #fbeae8;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-400: #9ca3af; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-800: #1f2937; --gray-900: #111827;
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            background: var(--gray-50);
            color: var(--gray-800);
            padding: 24px 16px;
            display: flex; align-items: center; justify-content: center;
        }
        .reg-wrapper { width: 100%; max-width: 560px; }
        .reg-card {
            background: white;
            border-radius: 16px;
            padding: 36px 32px 28px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            position: relative;
        }
        .reg-card::before {
            content: ''; position: absolute;
            top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--green) 0%, var(--yellow) 60%, var(--red) 100%);
            border-radius: 16px 16px 0 0;
        }
        .reg-brand { text-align: center; margin-bottom: 26px; }
        .reg-brand img {
            height: 56px; width: 56px;
            object-fit: cover;
            border-radius: 14px;
            margin: 0 auto 14px; display: block;
            box-shadow: 0 4px 14px rgba(27, 94, 58, 0.22);
        }
        .reg-brand h1 {
            font-size: 22px; font-weight: 800;
            color: var(--gray-900); letter-spacing: -0.5px; margin: 0;
        }
        .reg-brand p { font-size: 13px; color: var(--gray-500); margin: 6px 0 0; }
        .form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 11px 14px;
            border: 1.5px solid var(--gray-200);
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(27, 94, 58, 0.08);
        }
        .alert-custom {
            border-radius: 10px; padding: 12px 16px; font-size: 13px;
            background: var(--red-light); color: var(--red);
            border-left: 4px solid var(--red);
            margin-bottom: 16px;
        }
        .alert-info-custom {
            border-radius: 10px; padding: 12px 16px; font-size: 13px;
            background: var(--green-light); color: var(--green-dark);
            border-left: 4px solid var(--green);
            margin-bottom: 20px;
        }
        .btn-register {
            width: 100%; padding: 12px;
            border: none; border-radius: 10px;
            background: var(--green); color: white;
            font-size: 14px; font-weight: 600;
        }
        .btn-register:hover { background: var(--green-dark); }
        .btn-register:disabled { background: var(--gray-400); cursor: not-allowed; }
        .helper { font-size: 11.5px; color: var(--gray-500); margin-top: 5px; }
        .back-link {
            text-align: center; margin-top: 18px;
            font-size: 13px; color: var(--gray-500);
        }
        .back-link a { color: var(--green); font-weight: 600; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="reg-wrapper">
        <div class="reg-card">
            <div class="reg-brand">
                <img src="{{ asset('images/logo.webp') }}" alt="CROPS">
                <h1>Farmer Registration</h1>
                <p>Register to access rice yield predictions for your farm</p>
            </div>

            @if ($errors->any())
                <div class="alert-custom">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="alert-info-custom">
                <i class="bi bi-info-circle-fill me-2"></i>
                Your account will be <strong>verified by the CAO</strong> after registration.
                You can log in and use the system while waiting.
            </div>

            <form method="POST" action="{{ route('farmer.register.submit') }}" id="regForm">
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
                            <option value="{{ $brgy }}" {{ old('barangay') == $brgy ? 'selected' : '' }}>
                                {{ $brgy }}
                            </option>
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
                    <button type="submit" class="btn-register" id="regBtn">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        <span id="regBtnText">Create Account</span>
                    </button>
                </div>
            </form>

            <div class="back-link">
                Already have an account?
                <a href="{{ route('login') }}">Log in →</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('regForm').addEventListener('submit', function () {
            const btn = document.getElementById('regBtn');
            const text = document.getElementById('regBtnText');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating account...';
        });
    </script>
</body>
</html>