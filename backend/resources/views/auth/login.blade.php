<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CROPS — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --green: #0f4c2b;
            --green-dark: #0a381f;
            --gold: #b8860b;
            --red: #b22222;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: white;
            border-radius: var(--radius);
            padding: 40px 36px 32px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04);
            border: 1px solid var(--gray-200);
            position: relative;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--green), var(--gold), var(--red));
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-brand .logo {
            width: 56px;
            height: 56px;
            background: var(--green);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            color: var(--gold);
            font-size: 26px;
        }
        .login-brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.5px;
            margin: 0;
        }
        .login-brand h1 span { color: var(--gold); }
        .login-brand p {
            font-size: 13px;
            color: var(--gray-500);
            margin: 4px 0 0;
        }
        .login-brand .sub {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
        }
        .form-control {
            border-radius: 10px;
            padding: 11px 16px;
            border: 1.5px solid var(--gray-200);
            font-size: 14px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(15, 76, 43, 0.08);
        }
        .input-group-text {
            border: 1.5px solid var(--gray-200);
            border-right: none;
            background: var(--gray-50);
            color: var(--gray-500);
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control { border-radius: 0 10px 10px 0; }

        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--green);
            color: white;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.15s ease;
        }
        .btn-login:hover { background: var(--green-dark); }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            background: #fde8e8;
            color: var(--red);
            border-left: 4px solid var(--red);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: var(--gray-400);
        }
        .login-footer .dots span {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin: 0 3px;
        }
        .login-footer .dots span:nth-child(1) { background: var(--red); }
        .login-footer .dots span:nth-child(2) { background: var(--gold); }
        .login-footer .dots span:nth-child(3) { background: var(--green); }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="logo"><i class="bi bi-tree-fill"></i></div>
                <h1>CROPS</h1>
                <p>Rice Yield Prediction &amp; Analysis</p>
                <div class="sub">Santiago City Agriculture Office</div>
            </div>

            @if ($errors->any())
                <div class="alert-custom mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin@cao.gov.ph" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                </button>
            </form>

            <div class="login-footer">
                <div class="dots"><span></span><span></span><span></span></div>
                <span class="text-muted">City Agriculture Office</span>
            </div>
        </div>
    </div>
</body>
</html>