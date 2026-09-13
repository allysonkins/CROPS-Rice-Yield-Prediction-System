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
            --green: #0f4c2b;
            --green-dark: #0a381f;
            --gold: #b8860b;
            --red: #b22222;
            --gray-50: #f9fafb;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 16px;
            padding: 40px 36px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid var(--gray-200);
            max-width: 460px;
            width: 100%;
            position: relative;
            text-align: center;
        }
        .verify-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--green), var(--gold), var(--red));
            border-radius: 16px 16px 0 0;
        }
        .verify-card img {
            height: 60px;
            width: 60px;
            object-fit: cover;
            border-radius: 14px;
            margin: 0 auto 16px;
            display: block;
            box-shadow: 0 4px 14px rgba(15, 76, 43, 0.22);
        }
        .verify-card h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.4px;
            margin-bottom: 4px;
        }
        .verify-card .brand-accent {
            width: 30px; height: 3px; border-radius: 2px;
            background: var(--gold);
            margin: 9px auto 20px;
        }
        .verify-card p {
            font-size: 14px;
            color: var(--gray-600);
            line-height: 1.55;
            margin-bottom: 20px;
        }
        .verify-card .alert {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            text-align: left;
            margin-bottom: 20px;
        }
        .btn-verify {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--green);
            color: white;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.15s ease;
            margin-bottom: 10px;
        }
        .btn-verify:hover { background: var(--green-dark); }
        .btn-verify:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-logout {
            background: transparent;
            border: 1.5px solid var(--gray-200);
            color: var(--gray-600);
            padding: 10px;
            border-radius: 10px;
            width: 100%;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-logout:hover { background: var(--gray-50); color: var(--gray-800); }
    </style>
</head>
<body>
    <div class="verify-card">
        <img src="{{ asset('images/logo.webp') }}" alt="CROPS Logo">
        <h1>Verify Your Email</h1>
        <div class="brand-accent"></div>

        <p>
            Thanks for signing up, <strong>{{ auth()->user()->name }}</strong>!<br>
            We sent a verification link to:
            <br><strong style="color: var(--green);">{{ auth()->user()->email }}</strong>
        </p>

        <p style="font-size: 13px;">
            Click the link in that email to activate your account and continue.
            If you didn't receive it, we can send another one.
        </p>

        @if (session('status') === 'verification-link-sent')
            <div class="alert" style="background: #eaf6ee; color: var(--green); border-left: 4px solid var(--green);">
                <i class="bi bi-check-circle-fill"></i>
                A new verification link has been sent to your email.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
            @csrf
            <button type="submit" class="btn-verify" id="resendBtn">
                <i class="bi bi-envelope-arrow-up me-2"></i>
                <span id="resendText">Resend Verification Email</span>
            </button>
        </form>

        <a href="{{ route('logout') }}" class="btn-logout">
            <i class="bi bi-box-arrow-right me-1"></i>
            Sign Out
        </a>
    </div>

    <script>
        document.getElementById('resendForm').addEventListener('submit', function() {
            const btn = document.getElementById('resendBtn');
            const text = document.getElementById('resendText');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending...';
        });
    </script>
</body>
</html>