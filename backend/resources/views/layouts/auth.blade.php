<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — CROPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">
    <style>
        :root {
            --green: #1b5e3a; --green-mid: #4a9c5d; --green-dark: #0d3d24; --green-light: #eaf7ee;
            --yellow: #f2b705; --red: #c0392b; --red-light: #fbeae8;
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb; --gray-400: #9ca3af;
            --gray-500: #6b7280; --gray-600: #4b5563; --gray-700: #374151; --gray-900: #111827;
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh; background: var(--gray-50); color: var(--gray-900);
            -webkit-font-smoothing: antialiased;
        }
        .auth-shell { display: flex; min-height: 100vh; }

        /* ---------- Left: government / agriculture panel ---------- */
        .auth-side {
            flex: 0 0 44%; position: relative; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 40px 48px 36px; color: #fff;
            background: linear-gradient(160deg, var(--green-dark) 0%, var(--green) 65%, #226b44 100%);
        }
        .auth-side::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; z-index: 3;
            background: linear-gradient(90deg, var(--green-mid) 0%, var(--yellow) 60%, var(--red) 100%);
        }
        .auth-side::after { /* soft field-grid texture */
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: repeating-linear-gradient(115deg, rgba(255,255,255,.035) 0 2px, transparent 2px 26px);
        }
        .field { position: absolute; left: 0; right: 0; bottom: 0; width: 100%; height: 46%; z-index: 1; }
        .side-top, .side-mid, .side-foot { position: relative; z-index: 2; }
        .agency { display: flex; align-items: center; gap: 14px; }
        .agency img {
            width: 54px; height: 54px; object-fit: cover; border-radius: 14px;
            border: 2px solid rgba(255,255,255,.85); box-shadow: 0 4px 14px rgba(0,0,0,.25);
        }
        .agency small { display: block; font-size: 11.5px; letter-spacing: .3px; color: rgba(255,255,255,.72); }
        .agency strong { display: block; font-size: 15px; font-weight: 700; line-height: 1.25; }
        .side-mid h2 { font-size: 40px; font-weight: 800; letter-spacing: -1px; line-height: 1.05; }
        .side-mid .accent { width: 44px; height: 4px; border-radius: 2px; background: var(--yellow); margin: 16px 0 14px; }
        .side-mid p { font-size: 15px; line-height: 1.6; color: rgba(255,255,255,.82); max-width: 380px; }
        .side-list { list-style: none; margin-top: 26px; display: grid; gap: 13px; }
        .side-list li { display: flex; align-items: center; gap: 12px; font-size: 13.5px; font-weight: 500; color: rgba(255,255,255,.92); }
        .side-list i {
            width: 32px; height: 32px; flex: none; display: grid; place-items: center;
            border-radius: 9px; background: rgba(255,255,255,.12); color: var(--yellow); font-size: 15px;
        }
        .side-foot { font-size: 11.5px; color: rgba(255,255,255,.6); }

        /* ---------- Right: form ---------- */
        .auth-main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 32px 20px; }
        .auth-card {
            width: 100%; max-width: 420px; background: #fff; border-radius: 18px;
            padding: 38px 32px 28px; border: 1px solid var(--gray-200); box-shadow: var(--shadow-lg); position: relative;
        }
        .auth-card.wide { max-width: 560px; }
        .auth-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, var(--green) 0%, var(--green-mid) 45%, var(--yellow) 88%, var(--red) 100%);
            border-radius: 18px 18px 0 0;
        }
        .brand { text-align: center; margin-bottom: 26px; }
        .brand h1 { font-size: 24px; font-weight: 800; letter-spacing: -.5px; line-height: 1.1; }
        .brand .accent { width: 30px; height: 3px; border-radius: 2px; background: var(--yellow); margin: 10px auto 8px; }
        .brand p { font-size: 13.5px; font-weight: 600; color: var(--gray-700); margin: 0; }
        .brand .role {
            display: inline-block; margin-top: 8px; padding: 3px 10px; border-radius: 20px;
            background: var(--green-light); color: var(--green-dark);
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px;
        }
        .brand .role.staff { background: #eef2ff; color: #3730a3; }
        .mobile-logo { display: none; height: 56px; width: 56px; object-fit: cover; border-radius: 14px; margin: 0 auto 12px; box-shadow: 0 4px 14px rgba(27,94,58,.22); }

        .form-label { font-size: 13px; font-weight: 600; color: var(--gray-700); margin-bottom: 6px; }
        .form-control, .form-select {
            border-radius: 12px; padding: 13px 16px; border: 1.5px solid var(--gray-200); font-size: 15px;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .form-control:focus, .form-select:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(27,94,58,.1); }
        .input-group-text {
            border: 1.5px solid var(--gray-200); border-right: none; background: var(--gray-50);
            color: var(--gray-500); border-radius: 12px 0 0 12px; font-size: 15px;
        }
        .input-group .form-control { border-radius: 0 12px 12px 0; }
        .input-group .form-control:not(:last-child) { border-radius: 0; }
        .pw-toggle {
            border: 1.5px solid var(--gray-200); border-left: none; background: var(--gray-50); color: var(--gray-500);
            border-radius: 0 12px 12px 0; padding: 0 14px; cursor: pointer;
        }
        .pw-toggle:hover { color: var(--green); }
        .helper { font-size: 11.5px; color: var(--gray-500); margin-top: 5px; }
        .btn-primary-cta {
            width: 100%; padding: 13px; border: none; border-radius: 12px; background: var(--green); color: #fff;
            font-size: 15px; font-weight: 700; letter-spacing: .2px; transition: background .15s ease;
        }
        .btn-primary-cta:hover { background: var(--green-dark); }
        .btn-primary-cta:disabled { background: var(--gray-400); cursor: not-allowed; }
        .btn-outline-cta {
            width: 100%; padding: 11px; border: 1.5px solid var(--gray-200); border-radius: 12px;
            background: transparent; color: var(--gray-600); font-size: 13px; font-weight: 500; cursor: pointer;
        }
        .btn-outline-cta:hover { background: var(--gray-50); color: var(--gray-900); }
        .alert-custom, .alert-status, .alert-info-custom {
            border-radius: 12px; padding: 12px 16px; font-size: 13.5px; margin-bottom: 18px;
        }
        .alert-custom { background: var(--red-light); color: var(--red); border-left: 4px solid var(--red); }
        .alert-status, .alert-info-custom { background: var(--green-light); color: var(--green-dark); border-left: 4px solid var(--green); }
        .card-foot { text-align: center; margin-top: 22px; padding-top: 20px; border-top: 1px solid var(--gray-200); }
        .card-foot p { font-size: 13px; color: var(--gray-500); margin: 0 0 6px; }
        .card-foot a { font-size: 14px; font-weight: 700; color: var(--green); text-decoration: none; }
        .card-foot a:hover { text-decoration: underline; }
        .below { text-align: center; margin-top: 20px; font-size: 12px; }
        .below a { color: var(--gray-500); text-decoration: none; font-weight: 500; }
        .below a:hover { color: var(--gray-700); }
        .secure-note { margin-top: 18px; font-size: 11.5px; color: var(--gray-400); text-align: center; }

        @media (max-width: 991.98px) {
            .auth-shell { flex-direction: column; }
            .auth-side { flex: none; padding: 26px 24px 22px; }
            .side-mid, .side-foot, .field { display: none; }
            .auth-main { padding: 24px 16px 32px; }
        }
        @media (max-width: 575.98px) { .auth-card { padding: 32px 22px 24px; } }
    </style>
</head>
<body>
<div class="auth-shell">
    <aside class="auth-side">
        <div class="side-top agency">
            <img src="{{ asset('images/logo.webp') }}" alt="CROPS logo">
            <div>
                <small>Santiago City</small>
                <strong>City Agriculture Office</strong>
            </div>
        </div>

        <div class="side-mid">
            <h2>CROPS</h2>
            <div class="accent"></div>
            <p>A rice yield prediction system that helps farmers and the City Agriculture Office plan every season with confidence.</p>
            <ul class="side-list">
                <li><i class="bi bi-graph-up-arrow"></i> Rice yield predictions for your farm</li>
                <li><i class="bi bi-patch-check-fill"></i> Farmer records verified by the CAO</li>
                <li><i class="bi bi-shield-lock-fill"></i> Secure, role-based access</li>
            </ul>
        </div>

        <div class="side-foot">© {{ date('Y') }} City Agriculture Office. Authorized use only.</div>

        <svg class="field" viewBox="0 0 400 160" preserveAspectRatio="xMidYMax slice" aria-hidden="true">
            <defs>
                <symbol id="stalk" viewBox="0 0 24 80" overflow="visible">
                    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                        <path d="M8 80 C8 52 10 34 19 12"/>
                        <path d="M19 12 l4 -7 M17 18 l-6 -4 M16 24 l6 -3 M14 31 l-6 -3 M13 38 l5 -3 M11 45 l-5 -2"/>
                    </g>
                </symbol>
            </defs>
            <path d="M0 118 Q100 88 200 112 T400 102 V160 H0Z" fill="rgba(255,255,255,.06)"/>
            <path d="M0 140 Q120 112 240 136 T400 128 V160 H0Z" fill="rgba(255,255,255,.09)"/>
            <g style="color: rgba(242,183,5,.6)">
                <use href="#stalk" x="18"  y="88" width="24" height="72"/>
                <use href="#stalk" x="70"  y="80" width="26" height="80"/>
                <use href="#stalk" x="122" y="92" width="22" height="68"/>
                <use href="#stalk" x="176" y="84" width="26" height="76"/>
                <use href="#stalk" x="228" y="94" width="22" height="66"/>
                <use href="#stalk" x="280" y="82" width="26" height="78"/>
                <use href="#stalk" x="332" y="90" width="24" height="70"/>
                <use href="#stalk" x="376" y="98" width="20" height="62"/>
            </g>
        </svg>
    </aside>

    <main class="auth-main">
        <div class="auth-card @yield('card_class')">
            <img class="mobile-logo" src="{{ asset('images/logo.webp') }}" alt="CROPS">
            @yield('content')
        </div>
        @hasSection('below')
            <div class="below">@yield('below')</div>
        @endif
    </main>
</div>

<script>
    function togglePassword(inputId = 'password', iconId = 'pwIcon') {
        const input = document.getElementById(inputId), icon = document.getElementById(iconId);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
    }
    // Any <form data-loading="Signing in..."> disables its .js-submit button on submit
    document.querySelectorAll('form[data-loading]').forEach(function (f) {
        f.addEventListener('submit', function () {
            const b = f.querySelector('.js-submit');
            if (!b) return;
            b.disabled = true;
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + f.dataset.loading;
        });
    });
</script>
@stack('scripts')
</body>
</html>