<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CROPS — @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><text y='14' font-size='14'>🌾</text></svg>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <style>
        /* ─── Root Variables ─── */
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
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.08);
            --radius: 12px;
            --radius-lg: 16px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 8px; }

        /* ─── Sidebar ─── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: var(--green);
            padding: 20px 0 16px;
            display: flex;
            flex-direction: column;
            z-index: 1050;
            transition: transform 0.3s ease;
            overflow: hidden; /* Hide scrollbar */
        }
        .sidebar-brand {
            padding: 0 20px 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-brand .logo {
            width: 34px;
            height: 34px;
            background: rgba(255,255,255,0.10);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 17px;
            flex-shrink: 0;
        }
        .sidebar-brand h1 {
            font-size: 17px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
            margin: 0;
        }
        .sidebar-brand h1 span { color: var(--gold); }

        .sidebar-nav {
            flex: 1;
            padding: 8px 10px 0;
            overflow-y: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        .sidebar-nav::-webkit-scrollbar { display: none; } /* Chrome/Safari */

        .sidebar-nav .nav-label {
            font-size: 9.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.25);
            padding: 8px 10px 4px;
            margin-top: 8px;
        }
        .sidebar-nav .nav-label:first-of-type {
            margin-top: 0;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.65);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
            text-decoration: none;
            margin-bottom: 1px;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.07);
            color: white;
        }
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.09);
            color: white;
            border-left-color: var(--gold);
        }
        .sidebar-nav a i {
            font-size: 16px;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar-nav a.active i { color: var(--gold); }

        .sidebar-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin: 8px 12px;
        }

        .sidebar-footer {
            padding: 0 10px 12px;
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.45);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }
        .sidebar-footer a i { font-size: 16px; width: 18px; text-align: center; }

        /* ─── Main Content ─── */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px 32px 40px;
        }

        /* ─── Top Bar ─── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--gray-200);
        }
        .topbar h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            letter-spacing: -0.3px;
        }
        .topbar h2 i { color: var(--green); margin-right: 8px; }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700);
            background: white;
            padding: 6px 16px 6px 6px;
            border-radius: 50px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }
        .topbar .user-badge .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        /* ─── Toast / Flash ─── */
        .alert {
            border: none;
            border-radius: var(--radius);
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow-md);
        }
        .alert-success { background: var(--green-light); color: var(--green); border-left: 4px solid var(--green); }
        .alert-warning { background: var(--gold-light); color: #7a6200; border-left: 4px solid var(--gold); }
        .alert-danger { background: var(--red-light); color: var(--red); border-left: 4px solid var(--red); }

        /* ─── Cards ─── */
        .card-custom {
            background: white;
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: box-shadow 0.2s ease;
        }
        .card-custom:hover { box-shadow: var(--shadow-md); }
        .card-custom .card-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-custom .card-title i { color: var(--green); font-size: 18px; }

        /* ─── Stat Cards ─── */
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: box-shadow 0.2s ease;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); }
        .stat-card .stat-info .label {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-500);
            margin-bottom: 4px;
        }
        .stat-card .stat-info .value {
            font-size: 26px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        .stat-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-card:nth-child(1) .stat-icon { background: #eaf6ee; color: var(--green); }
        .stat-card:nth-child(2) .stat-icon { background: #eef2ff; color: #4f46e5; }
        .stat-card:nth-child(3) .stat-icon { background: #faf3e4; color: var(--gold); }
        .stat-card:nth-child(4) .stat-icon { background: #fde8e8; color: var(--red); }

        /* ─── Badges ─── */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px 4px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }
        .badge-status .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .badge-status.high .dot { background: var(--green); }
        .badge-status.medium .dot { background: var(--gold); }
        .badge-status.low .dot { background: var(--red); }

        /* ─── Table ─── */
        .table thead th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
            border-bottom: 2px solid var(--gray-200);
            padding: 12px 12px;
        }
        .table td {
            font-size: 13px;
            padding: 12px 12px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
        }
        .table tbody tr:hover { background: var(--gray-50); }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: 280px; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .topbar { flex-wrap: wrap; gap: 10px; }
            .stat-card .stat-info .value { font-size: 20px; }
        }
    </style>
</head>
<body>

    <!-- ─── SIDEBAR ─── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <h1>CROPS</h1>
        </div>

        <nav class="sidebar-nav">
            <!-- Main -->
            <div class="nav-label">Main</div>

            @php
                $dashboardUrl = '/admin/dashboard';
                $dashboardRoute = 'admin.dashboard';
                if (auth()->check()) {
                    if (auth()->user()->role === 'staff') {
                        $dashboardUrl = '/staff/dashboard';
                        $dashboardRoute = 'staff.dashboard';
                    } elseif (auth()->user()->role === 'farmer') {
                        $dashboardUrl = '/farmer/dashboard';
                        $dashboardRoute = 'farmer.dashboard';
                    }
                }
            @endphp
            <a href="{{ $dashboardUrl }}" class="nav-link {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>

            @if(auth()->check() && auth()->user()->role !== 'farmer')
                <a href="/admin/predictions" class="{{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i> <span>Predictions</span>
                </a>
            @endif

            {{-- Farmer only: My Predictions --}}
            @if(auth()->check() && auth()->user()->role === 'farmer')
                <a href="/farmer/predictions" class="{{ request()->routeIs('farmer.predictions.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i> <span>My Predictions</span>
                </a>
            @endif

            <a href="/admin/rice-varieties" class="{{ request()->routeIs('admin.rice-varieties.*') ? 'active' : '' }}">
                <i class="bi bi-flower1"></i> <span>Rice Varieties</span>
            </a>

            <!-- People -->
            <div class="nav-label" style="margin-top:8px;">People</div>

            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="/admin/users" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> <span>Staff Accounts</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->role !== 'farmer')
                <a href="/admin/farmers" class="{{ request()->routeIs('admin.farmers.*') ? 'active' : '' }}">
                    <i class="bi bi-person-lines-fill"></i> <span>Farmers List</span>
                </a>
            @endif

            <!-- Land -->
            <div class="nav-label" style="margin-top:8px;">Land</div>
            <a href="/admin/map" class="{{ request()->routeIs('admin.map') ? 'active' : '' }}">
                <i class="bi bi-map"></i> <span>Farms & Map</span>
            </a>

            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="/admin/farms" class="{{ request()->routeIs('admin.farms.*') ? 'active' : '' }}">
                    <i class="bi bi-list-ul"></i> <span>Manage Farms</span>
                </a>
            @endif

            <a href="/admin/farm-records" class="{{ request()->routeIs('admin.farm-records.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data-fill"></i> <span>Farm Records</span>
            </a>

            <!-- Alerts & Reports -->
            <div class="nav-label" style="margin-top:8px;">Alerts & Reports</div>
            <a href="/admin/advisories" class="{{ request()->routeIs('admin.advisories.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> <span>Advisories</span>
            </a>

            {{-- Reports — HIDDEN for farmers --}}
            @if(auth()->check() && auth()->user()->role !== 'farmer')
                <a href="/admin/reports" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> <span>Reports</span>
                </a>
            @endif

            <!-- ===== My Profile for ALL users ===== -->
            <div class="nav-label" style="margin-top:8px;">Account</div>
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-person"></i> <span>My Profile</span>
            </a>
        </nav>

        <hr class="sidebar-divider">

        <div class="sidebar-footer">
            <a href="/logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>

    <!-- ─── MAIN ─── -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <h2><i class="bi bi-tree"></i> @yield('title', 'Dashboard')</h2>
            <div class="topbar-right">
                <span class="user-badge">
                    <span class="avatar"><i class="bi bi-person-fill"></i></span>
                    {{ auth()->user()->name ?? 'Guest' }}
                </span>
                <button class="btn btn-light border d-md-none" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('scripts')
</body>
</html>