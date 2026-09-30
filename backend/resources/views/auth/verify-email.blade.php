<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CROPS — Verify Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <style>
        :root {
            --brand-green: #165b33;
            --brand-green-dark: #0d381f;
            --brand-green-light: #eaf6ee;
            --brand-green-subtle: #f2f9f4;
            --brand-gold: #d97706;
            --brand-danger: #dc2626;

            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-300: #cbd5e1;
            --slate-400: #94a3b8;
            --slate-500: #64748b;
            --slate-600: #475569;
            --slate-700: #334155;
            --slate-800: #1e293b;
            --slate-900: #0f172a;

            --shadow-lg: 0 12px 24px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -2px rgba(15, 23, 42, 0.03);
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--slate-50);
            color: var(--slate-800);
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .verify-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 40px 36px 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--slate-200);
            max-width: 460px;
            width: 100%;
            position: relative;
        }

        .verify-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--brand-green) 0%, var(--brand-gold) 100%);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        /* Brand block */
        .brand {
            text-align: center;
            margin-bottom: 22px;
        }

        .brand img {
            height: 60px;
            width: 60px;
            object-fit: cover;
            border-radius: 14px;
            margin: 0 auto 16px;
            display: block;
            box-shadow: 0 4px 14px rgba(22, 91, 51, 0.22);
        }

        .brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.4px;
            margin: 0;
        }

        .brand .accent {
            width: 30px;
            height: 3px;
            border-radius: 2px;
            background: var(--brand-gold);
            margin: 10px auto 12px;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #eef2ff;
            color: #3730a3;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Message body */
        .message {
            font-size: 14px;
            color: var(--slate-600);
            line-height: 1.6;
            text-align: center;
            margin-bottom: 20px;
        }

        .message strong {
            color: var(--slate-800);
        }

        .email-highlight {
            color: var(--brand-green);
            font-weight: 700;
            word-break: break-all;
        }

        /* Alert states */
        .alert-custom {
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 13px;
            text-align: left;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-success {
            background: var(--brand-green-subtle);
            color: var(--brand-green-dark);
            border-left: 4px solid var(--brand-green);
        }

        .alert-success i { color: var(--brand-green); }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--brand-danger);
        }

        .alert-danger i { color: var(--brand-danger); }

        /* Buttons */
        .btn-verify {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: var(--radius-md);
            background: var(--brand-green);
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.15s ease;
            margin-bottom: 10px;
        }

        .btn-verify:hover { background: var(--brand-green-dark); }

        .btn-verify:disabled {
            background: var(--slate-400);
            cursor: not-allowed;
        }

        .btn-logout {
            width: 100%;
            padding: 11px;
            border: 1.5px solid var(--slate-200);
            border-radius: var(--radius-md);
            background: transparent;
            color: var(--slate-600);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: var(--slate-50);
            color: var(--slate-800);
            border-color: var(--slate-300);
        }

        .btn-logout i { margin-right: 4px; }

        /* Divider between actions */
        .divider {
            border: none;
            border-top: 1px solid var(--slate-200);
            margin: 20px 0 16px;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        {{-- Brand header --}}
        <div class="brand">
            <img src="{{ asset('images/logo.webp') }}" alt="CROPS Logo">
            <h1>Verify Your Email</h1>
            <div class="accent"></div>
            <span class="role-badge">Staff &amp; Admin Only</span>
        </div>

        {{-- Message --}}
        <p class="message">
            Thanks for signing up, <strong>{{ auth()->user()->name }}</strong>!
            <br>
            We sent a verification link to:
            <br>
            <span class="email-highlight">{{ auth()->user()->email }}</span>
        </p>

        <p class="message" style="font-size: 13px; color: var(--slate-500);">
            Click the link in that email to activate your account and continue.
            If you didn't receive it, we can send another one.
        </p>

        {{-- Success flash from resend --}}
        @if (session('status') === 'verification-link-sent')
            <div class="alert-custom alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <div>A new verification link has been sent to your email.</div>
            </div>
        @endif

        {{-- Error flash --}}
        @if ($errors->any())
            <div class="alert-custom alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        {{-- Resend form --}}
        <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
            @csrf
            <button type="submit" class="btn-verify" id="resendBtn">
                <i class="bi bi-envelope-arrow-up me-1"></i>
                <span id="resendText">Resend Verification Email</span>
            </button>
        </form>

        <hr class="divider">

        {{-- Logout — POST form, works on mobile --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i>
                Sign Out
            </button>
        </form>
    </div>

    <script>
        // Resend button loading state
        const resendForm = document.getElementById('resendForm');
        if (resendForm) {
            resendForm.addEventListener('submit', function () {
                const btn = document.getElementById('resendBtn');
                const text = document.getElementById('resendText');
                btn.disabled = true;
                text.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending...';
            });
        }
    </script>
</body>
</html>