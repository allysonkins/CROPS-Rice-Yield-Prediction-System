<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CROPS - @yield('title', 'Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #1a4d2e;
            --secondary-green: #2e7d32;
            --light-green: #4caf50;
            --bg-gray: #f4f7f9;
            --text-dark: #1e293b;
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: var(--bg-gray); }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--primary-green);
            color: white;
            padding: 20px 0;
            transition: all 0.3s;
            z-index: 1030;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar .brand {
            font-size: 24px;
            font-weight: 700;
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .brand i { color: var(--light-green); }
        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 20px;
            margin: 4px 10px;
            border-radius: 10px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: var(--secondary-green);
            color: white;
        }
        .sidebar .nav-link i { font-size: 20px; width: 24px; }

        .main-content {
            margin-left: 260px;
            padding: 25px 35px;
        }
        .top-nav {
            background: white;
            padding: 15px 25px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-nav .page-title { font-weight: 600; color: var(--text-dark); margin: 0; }
        .top-nav .user-badge {
            background: var(--bg-gray);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-card {
            background: white;
            padding: 20px 25px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 5px solid var(--secondary-green);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card .label { color: #64748b; font-size: 14px; font-weight: 500; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--text-dark); }
        .stat-card .icon { float: right; font-size: 32px; color: var(--light-green); opacity: 0.6; }

        .card-custom {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: none;
            height: 100%;
        }
        .card-custom .card-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .list-group-item-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .list-group-item-custom:last-child { border-bottom: none; }
        .badge-low { background: #fee2e2; color: #b91c1c; padding: 4px 12px; border-radius: 30px; font-weight: 500; }
        .badge-normal { background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 30px; font-weight: 500; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .brand span { display: none; }
            .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="brand">
            <i class="bi bi-tree"></i> <span>CROPS</span>
        </div>
        <div class="mt-3">
    <a href="/admin/dashboard" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>
    <a href="/admin/predictions" class="nav-link {{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}">
        <i class="bi bi-graph-up"></i> <span>Predictions</span>
    </a>
    <a href="/admin/rice-varieties" class="nav-link {{ request()->routeIs('admin.rice-varieties.*') ? 'active' : '' }}">
        <i class="bi bi-flower1"></i> <span>Rice Varieties</span>
    </a>
    
    @if(auth()->check() && auth()->user()->role === 'admin')
    <a href="/admin/users" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> <span>User Management</span>
    </a>
    @endif

    <a href="/admin/map" class="nav-link {{ request()->routeIs('admin.map') ? 'active' : '' }}">
        <i class="bi bi-map"></i> <span>Farm Map</span>
    </a>
    <a href="/admin/advisories" class="nav-link {{ request()->routeIs('admin.advisories.*') ? 'active' : '' }}">
        <i class="bi bi-megaphone"></i> <span>Advisories</span>
    </a>
    <a href="/admin/reports" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-pdf"></i> <span>Reports</span>
    </a>
</div>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 20px;">
        <a href="/logout" class="nav-link" style="color: #f87171;">
            <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- Top Nav -->
        <div class="top-nav">
            <h5 class="page-title">@yield('title', 'Admin Panel')</h5>
            <div>
                <span class="user-badge">
                    <i class="bi bi-person-circle me-1"></i> 
                    {{ auth()->user()->name ?? 'Guest' }}
                </span>
            </div>
        </div>

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('scripts')
</body>
</html>