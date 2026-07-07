<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CROPS - Login</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #1a4d2e; --secondary-green: #2e7d32; --light-green: #4caf50; }
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #f4f7f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 24px;
            padding: 45px 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(26, 77, 46, 0.15);
        }
        .login-container .brand {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-container .brand i {
            font-size: 48px;
            color: var(--primary-green);
            background: #e8f5e9;
            padding: 15px;
            border-radius: 16px;
        }
        .login-container .brand h2 {
            font-weight: 700;
            color: var(--primary-green);
            margin-top: 12px;
        }
        .login-container .brand p {
            color: #64748b;
            font-size: 14px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: var(--secondary-green);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.15);
        }
        .btn-login {
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: var(--secondary-green);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 77, 46, 0.25);
        }
        .alert-custom {
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 14px;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            font-size: 13px;
        }
        .footer-text a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <i class="bi bi-tree"></i>
            <h2>CROPS</h2>
            <p>Rice Yield Prediction & Analysis System</p>
        </div>

        <!-- Display Validation Errors -->
        @if ($errors->any())
            <div class="alert alert-danger alert-custom">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0" style="border-radius:12px 0 0 12px;">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control" placeholder="admin@cao.gov.ph" value="{{ old('email') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0" style="border-radius:12px 0 0 12px;">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="footer-text">
            <p>🔒 Secure &bull; City Agriculture Office</p>
            <p class="mt-1">Demo: <strong>admin@cao.gov.ph</strong> / <strong>password</strong></p>
        </div>
    </div>
</body>
</html>