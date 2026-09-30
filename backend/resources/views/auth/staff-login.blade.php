<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CAO Staff Login — CROPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <style>
        :root {
            --green: #1b5e3a; --green-mid: #4a9c5d; --green-dark: #0d3d24;
            --yellow: #f2b705; --red: #c0392b; --red-light: #fbeae8;
            --gray-50: #f9fafb; --gray-200: #e5e7eb; --gray-400: #9ca3af;
            --gray-500: #6b7280; --gray-700: #374151; --gray-900: #111827;
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: var(--gray-50);
            color: var(--gray-900);
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }
        .login-wrapper { width: 100%; max-width: 420px; }
        .login-card {
            background: white;
            border-radius: 18px;
            padding: 40px 32px 28px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            position: relative;
        }
        .login-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--green) 0%, var(--green-mid) 45%, var(--yellow) 88%, var(--red) 100%);
            border-radius: 18px 18px 0 0;
        }
        .brand { text-align: center; margin-bottom: 26px; }
        .brand img {
            height: 64px; width: 64px; object-fit: cover;
            border-radius: 14px; display: block; margin: 0 auto 14px;
            box-shadow: 0 4px 14px rgba(27, 94, 58, 0.22);
        }
        .brand h1 {
            font-size: 24px; font-weight: 800;
            letter-spacing: -0.5px; line-height: 1; margin: 0;
            color: var(--gray-900);
        }
        .brand .accent {
            width: 30px; height: 3px; border-radius: 2px;
            background: var(--yellow); margin: 10px auto 8px;
        }
        .brand p {
            font-size: 13.5px; font-weight: 600; color: var(--gray-700); margin: 0;
        }
        .brand .role {
            display: inline-block; margin-top: 8px;
            padding: 3px 10px; border-radius: 20px;
            background: #eef2ff; color: #3730a3;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.6px;
        }
        .form-label {
            font-size: 13px; font-weight: 600; color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-control {
            border-radius: 12px;
            padding: 13px 16px;
            border: 1.5px solid var(--gray-200);
            font-size: 15px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(27, 94, 58, 0.08);
        }
        .input-group-text {
            border: 1.5px solid var(--gray-200);
            border-right: none;
            background: var(--gray-50);
            color: var(--gray-500);
            border-radius: 12px 0 0 12px;
            font-size: 15px;
        }
        .input-group .form-control { border-radius: 0 12px 12px 0; }
        .pw-toggle {
            border: 1.5px solid var(--gray-200);
            border-left: none;
            background: var(--gray-50);
            color: var(--gray-500);
            border-radius: 0 12px 12px 0;
            padding: 0 14px;
            cursor: pointer;
        }
        .pw-toggle:hover { color: var(--green); }
        .btn-login {
            width: 100%; padding: 13px;
            border: none; border-radius: 12px;
            background: var(--green); color: white;
            font-size: 15px; font-weight: 700;
            letter-spacing: 0.2px;
        }
        .btn-login:hover { background: var(--green-dark); }
        .btn-login:disabled { background: var(--gray-400); cursor: not-allowed; }
        .alert-custom {
            border-radius: 12px; padding: 12px 16px;
            font-size: 13.5px;
            background: var(--red-light); color: var(--red);
            border-left: 4px solid var(--red);
            margin-bottom: 18px;
        }
        .alert-status {
            border-radius: 12px; padding: 12px 16px;
            font-size: 13.5px;
            background: #eaf7ee; color: var(--green-dark);
            border-left: 4px solid var(--green);
            margin-bottom: 18px;
        }
        .farmer-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 12px;
        }
        .farmer-footer a {
            color: var(--gray-400);
            text-decoration: none;
            font-weight: 500;
        }
        .farmer-footer a:hover { color: var(--gray-600); }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand">
                <img src="{{ asset('images/logo.webp') }}" alt="CROPS">
                <h1>CROPS</h1>
                <div class="accent"></div>
                <p>City Agriculture Office</p>
                <div class="role">Staff &amp; Admin Login</div>
            </div>

            @if ($errors->any())
                <div class="alert-custom">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert-status">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('staff.login.submit') }}" id="loginForm">
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
                            placeholder="you@cao.gov.ph"
                            autocomplete="email"
                            inputmode="email"
                            autocapitalize="off"
                            spellcheck="false"
                            required
                            autofocus>
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
                            autocomplete="current-password"
                            required>
                        <button type="button" class="pw-toggle" onclick="togglePassword()" tabindex="-1">
                            <i class="bi bi-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="btnText">Sign In</span>
                </button>
            </form>
        </div>

        <div class="farmer-footer">
            <a href="{{ route('login') }}">
                <i class="bi bi-arrow-left"></i> Farmer Login
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('pwIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('btnText');
            btn.disabled = true;
            text.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Signing in...';
        });
    </script>
</body>
</html>