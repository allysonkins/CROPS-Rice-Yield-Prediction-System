<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CROPS — Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <style>
        :root {
            --green: #0f4c2b;
            --green-light: #eaf6ee;
            --green-dark: #0a381f;
            --gold: #b8860b;
            --gold-light: #f5e6c8;
            --red: #b22222;
            --red-light: #fde8e8;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --shadow-md: 0 4px 12px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08);
            --radius: 12px;
            --radius-lg: 16px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px 36px 32px;
            box-shadow: var(--shadow-lg);
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
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        /* Brand block */
        .login-brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-brand img {
            height: 60px;
            width: 60px;
            object-fit: cover;
            border-radius: 14px;
            margin: 0 auto 16px;
            display: block;
            box-shadow: 0 4px 14px rgba(15, 76, 43, 0.22);
        }
        .login-brand h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.5px;
            line-height: 1;
            margin: 0;
        }
        .login-brand .brand-accent {
            width: 30px;
            height: 3px;
            border-radius: 2px;
            background: var(--gold);
            margin: 9px auto 10px;
        }
        .login-brand p {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            margin: 0;
        }
        .login-brand .sub {
            font-size: 10.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--gray-400);
            margin-top: 6px;
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

        /* Password toggle */
        .pw-toggle {
            border: 1.5px solid var(--gray-200);
            border-left: none;
            background: var(--gray-50);
            color: var(--gray-500);
            border-radius: 0 10px 10px 0;
            padding: 0 14px;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .pw-toggle:hover { color: var(--green); }

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
        .btn-login:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            background: var(--red-light);
            color: var(--red);
            border-left: 4px solid var(--red);
        }

        .login-footer {
            text-align: center;
            margin-top: 22px;
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
        .login-footer .dots { margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <img src="{{ asset('images/logo.webp') }}" alt="CROPS Logo">
                <h1>CROPS</h1>
                <div class="brand-accent"></div>
                <p>Rice Yield Prediction &amp; Analysis</p>
                <div class="sub">Santiago City Agriculture Office</div>
            </div>

            @if ($errors->any())
                <div class="alert-custom mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success mb-3" style="border-radius: 10px; font-size: 13px;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            inputmode="email"
                            autocapitalize="off"
                            spellcheck="false">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required
                            autocomplete="current-password">
                        <button type="button" class="pw-toggle" onclick="togglePassword()" tabindex="-1" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    <span id="btnText">Sign In</span>
                </button>
            </form>

            <div class="login-footer">
                <div class="dots"><span></span><span></span><span></span></div>
                <span class="text-muted">City Agriculture Office</span>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('pwIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Prevent double-submit and show loading state
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('btnText');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in...';
        });
    </script>
</body>
</html>