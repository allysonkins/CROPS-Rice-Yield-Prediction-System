<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CROPS — @yield('title', 'Dashboard')</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">

    <!-- Framework & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/webp" href="{{ asset('images/logo.webp') }}">

    <style>
        /* ══════════════════════════════════════════════════════════
           1. DESIGN SYSTEM VARIABLES & FOUNDATIONS
           ══════════════════════════════════════════════════════════ */
        :root {
            /* CROPS Brand Colors */
            --brand-green: #165b33;
            --brand-green-dark: #0d381f;
            --brand-green-hover: #1b6d3d;
            --brand-green-mid: #25834b;
            --brand-green-light: #eaf6ee;
            --brand-green-subtle: #f2f9f4;

            --brand-gold: #d97706;
            --brand-gold-hover: #b45309;
            --brand-gold-light: #fef3c7;
            --brand-gold-subtle: #fffbeb;

            --brand-danger: #dc2626;
            --brand-danger-light: #fee2e2;

            /* Slate Neutral Scale */
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

            /* Government tricolor stripe (matches login pages) */
            --stripe: linear-gradient(90deg, #4a9c5d 0%, #f2b705 60%, #c0392b 100%);

            /* Elevation & Shadows */
            --shadow-xs: 0 1px 2px rgba(15, 23, 42, 0.04);
            --shadow-sm: 0 2px 4px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 4px 12px -2px rgba(15, 23, 42, 0.07), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 12px 24px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -2px rgba(15, 23, 42, 0.03);

            /* Geometry & Viewport */
            --app-height: 100dvh;
            --sidebar-width: 270px;
            --topbar-height: 70px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 9999px;
            --transition-smooth: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);

            /* Token Aliases & Backward Compatibility */
            --green: var(--brand-green);
            --green-dark: var(--brand-green-dark);
            --green-hover: var(--brand-green-hover);
            --green-mid: var(--brand-green-mid);
            --green-light: var(--brand-green-light);
            --green-subtle: var(--brand-green-subtle);

            --gold: var(--brand-gold);
            --gold-hover: var(--brand-gold-hover);
            --gold-light: var(--brand-gold-light);
            --gold-subtle: var(--brand-gold-subtle);

            --yellow: #d97706;
            --yellow-light: #fef3c7;
            --yellow-dark: #b45309;

            --red: var(--brand-danger);
            --red-light: var(--brand-danger-light);

            --gray-50: var(--slate-50);
            --gray-100: var(--slate-100);
            --gray-200: var(--slate-200);
            --gray-300: var(--slate-300);
            --gray-400: var(--slate-400);
            --gray-500: var(--slate-500);
            --gray-600: var(--slate-600);
            --gray-700: var(--slate-700);
            --gray-800: var(--slate-800);
            --gray-900: var(--slate-900);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--slate-50);
            color: var(--slate-800);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--slate-300); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--slate-400); }

        /* Accessible Focus Ring */
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(22, 91, 51, 0.25) !important;
        }

        /* ══════════════════════════════════════════════════════════
           2. SIDEBAR NAVIGATION
           ══════════════════════════════════════════════════════════ */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100%;
            height: 100vh;
            height: -webkit-fill-available;
            height: 100dvh;
            height: var(--app-height, 100dvh);
            max-height: 100%;
            max-height: var(--app-height, 100dvh);
            background: linear-gradient(180deg, var(--brand-green-dark) 0%, var(--brand-green) 100%);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            box-shadow: var(--shadow-lg);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), width 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--stripe);
            z-index: 3;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: repeating-linear-gradient(115deg, rgba(255,255,255,0.03) 0 2px, transparent 2px 26px);
        }

        .sidebar-brand {
            padding: 20px 20px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            min-width: 0;
        }

        .sidebar-brand img {
            height: 46px;
            width: 46px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
            flex-shrink: 0;
        }

        .brand-agency {
            display: block;
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.2;
            white-space: nowrap;
        }

        .sidebar-brand-text {
            min-width: 0;
        }

        .sidebar-brand-text h1 {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .sidebar-brand-text h1 span {
            color: #f2b705;
        }

        .sidebar-brand-text .brand-tagline {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            display: block;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-close-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #ffffff;
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            border-radius: var(--radius-sm);
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.15s ease;
            flex-shrink: 0;
            touch-action: manipulation;
        }

        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-nav {
            flex: 1 1 0px;
            min-height: 0;
            padding: 12px 14px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }

        .sidebar-nav .nav-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: rgba(255, 255, 255, 0.45);
            padding: 14px 12px 6px;
            margin-top: 4px;
        }

        .sidebar-nav .nav-label:first-of-type {
            margin-top: 0;
            padding-top: 4px;
        }

        .sidebar-nav a,
        .sidebar-nav button.sidebar-nav-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 14px;
            border-radius: var(--radius-md);
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition-smooth);
            text-decoration: none;
            margin-bottom: 3px;
            position: relative;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }

        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateX(3px);
        }

        .sidebar-nav button.sidebar-nav-btn:hover {
            background: rgba(239, 68, 68, 0.18);
            color: #fca5a5;
            transform: translateX(3px);
        }

        .sidebar-nav a.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            font-weight: 600;
            box-shadow: inset 3px 0 0 #fbbf24, var(--shadow-xs);
        }

        .sidebar-nav a i,
        .sidebar-nav button.sidebar-nav-btn i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.15s ease;
        }

        .sidebar-nav a:hover i,
        .sidebar-nav a.active i {
            color: #fbbf24;
        }

        .sidebar-nav button.sidebar-nav-btn:hover i {
            color: #f87171;
        }

        .sidebar-nav .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
            border-radius: var(--radius-full);
            font-weight: 700;
        }

        /* Sidebar User Card & Logout */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 12px 16px;
            padding-bottom: calc(14px + max(env(safe-area-inset-bottom, 0px), 8px));
            background: rgba(0, 0, 0, 0.22);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            position: relative;
            z-index: 2;
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }

        .sidebar-user-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            min-height: 36px;
            border-radius: var(--radius-full);
            background: #fbbf24;
            color: #451a03;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-user-details {
            min-width: 0;
            flex: 1;
        }

        .sidebar-user-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .sidebar-user-role {
            font-size: 0.68rem;
            color: rgba(255, 255, 255, 0.7);
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-footer-form {
            margin: 0;
            padding: 0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }

        .sidebar-logout-btn {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.18);
            width: 38px;
            height: 38px;
            min-width: 38px;
            min-height: 38px;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            flex-shrink: 0;
            touch-action: manipulation;
        }

        .sidebar-logout-btn:hover,
        .sidebar-logout-btn:focus-visible {
            background: var(--brand-danger);
            color: #ffffff;
            border-color: var(--brand-danger);
            box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.4);
        }

        /* Mobile Drawer Backdrop */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1030;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            touch-action: none;
        }

        .sidebar-backdrop.active {
            opacity: 1;
            visibility: visible;
        }

        /* ══════════════════════════════════════════════════════════
           3. TOPBAR & MAIN WRAPPER
           ══════════════════════════════════════════════════════════ */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .topbar {
            position: sticky;
            top: 0;
            height: var(--topbar-height);
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--slate-200);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 1020;
            transition: padding 0.2s ease;
        }

        .topbar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--stripe);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .mobile-nav-toggle {
            display: none;
            background: white;
            border: 1px solid var(--slate-200);
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--slate-700);
            cursor: pointer;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-xs);
        }

        .mobile-nav-toggle:hover {
            background: var(--slate-100);
            color: var(--brand-green);
        }

        .topbar-title-wrap {
            min-width: 0;
        }

        .topbar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--slate-900);
            margin: 0;
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-title i {
            color: var(--brand-green);
            font-size: 1.2rem;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid var(--slate-200);
            border-radius: var(--radius-full);
            padding: 5px 14px 5px 6px;
            box-shadow: var(--shadow-xs);
            transition: var(--transition-smooth);
            text-decoration: none;
            color: var(--slate-700);
        }

        .user-chip:hover {
            border-color: var(--slate-300);
            background: var(--slate-50);
            color: var(--slate-900);
        }

        .user-chip-avatar {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-full);
            background: var(--brand-green-light);
            color: var(--brand-green);
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-chip-meta {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }

        .user-chip-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--slate-800);
            max-width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-chip-role {
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--brand-green);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /* ══════════════════════════════════════════════════════════
           4. MAIN CONTENT & COHESIVE DASHBOARD STYLES
           ══════════════════════════════════════════════════════════ */
        .main-content {
            flex: 1;
            padding: 28px 32px 48px;
            width: 100%;
        }

        .content-container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* Card Custom */
        .card-custom {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--slate-200);
            transition: var(--transition-smooth);
            margin-bottom: 24px;
        }

        .card-custom:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--slate-300);
        }

        .card-custom .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--slate-900);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.2px;
        }

        .card-custom .card-title i {
            color: var(--brand-green);
            font-size: 1.25rem;
        }

        /* Stats Grid & Adaptive Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 20px 22px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: var(--transition-smooth);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--slate-300);
        }

        .stat-card .stat-info {
            min-width: 0;
        }

        .stat-card .stat-info .label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--slate-500);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .stat-card .stat-info .value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1.15;
            letter-spacing: -0.6px;
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .stat-card.stat-green .stat-icon,
        .stat-card:nth-child(1) .stat-icon { background: var(--brand-green-light); color: var(--brand-green); }
        .stat-card.stat-emerald .stat-icon,
        .stat-card:nth-child(2) .stat-icon { background: #e2f4e8; color: var(--brand-green-mid); }
        .stat-card.stat-amber .stat-icon,
        .stat-card:nth-child(3) .stat-icon { background: var(--brand-gold-light); color: var(--brand-gold); }
        .stat-card.stat-red .stat-icon,
        .stat-card:nth-child(4) .stat-icon { background: var(--brand-danger-light); color: var(--brand-danger); }

        /* Status Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px 4px 8px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-200);
            white-space: nowrap;
        }

        .badge-status .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge-status.high .dot { background: var(--brand-green); }
        .badge-status.medium .dot { background: var(--brand-gold); }
        .badge-status.low .dot { background: var(--brand-danger); }

        /* Responsive Data Tables */
        .table-responsive-custom {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--slate-200);
            background: #ffffff;
        }

        .table {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--slate-500);
            background: var(--slate-50);
            border-bottom: 1px solid var(--slate-200);
            padding: 14px 16px;
            white-space: nowrap;
        }

        .table td {
            font-size: 0.85rem;
            color: var(--slate-700);
            padding: 13px 16px;
            border-bottom: 1px solid var(--slate-100);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover td {
            background-color: var(--slate-50);
        }

        /* ══════════════════════════════════════════════════════════
           5. ALERTS & NOTIFICATIONS
           ══════════════════════════════════════════════════════════ */
        .alert-container {
            margin-bottom: 22px;
        }

        .alert-custom {
            border: none;
            border-radius: var(--radius-md);
            padding: 14px 18px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            position: relative;
            margin-bottom: 12px;
        }

        .alert-custom i.alert-icon {
            font-size: 1.25rem;
            line-height: 1.2;
            flex-shrink: 0;
        }

        .alert-custom .alert-content {
            flex: 1;
            min-width: 0;
        }

        .alert-custom .btn-close {
            padding: 0.6rem;
            margin: -0.3rem -0.3rem 0 auto;
            flex-shrink: 0;
        }

        .alert-custom-success {
            background-color: var(--brand-green-subtle);
            color: var(--brand-green-dark);
            border-left: 4px solid var(--brand-green);
        }
        .alert-custom-success i.alert-icon { color: var(--brand-green); }

        .alert-custom-warning {
            background-color: var(--brand-gold-subtle);
            color: #78350f;
            border-left: 4px solid var(--brand-gold);
        }
        .alert-custom-warning i.alert-icon { color: var(--brand-gold); }

        .alert-custom-danger {
            background-color: #fef2f2;
            color: #991b1b;
            border-left: 4px solid var(--brand-danger);
        }
        .alert-custom-danger i.alert-icon { color: var(--brand-danger); }

        /* Pending Farmer Verification Banner */
        .pending-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1px solid #fde68a;
            border-left: 5px solid var(--brand-gold);
            border-radius: var(--radius-lg);
            padding: 18px 22px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .pending-banner-left {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
        }

        .pending-banner .icon {
            font-size: 1.8rem;
            color: var(--brand-gold);
            flex-shrink: 0;
            line-height: 1;
            margin-top: 2px;
        }

        .pending-banner .body strong {
            display: block;
            color: #92400e;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .pending-banner .body p {
            margin: 0;
            font-size: 0.85rem;
            color: #78350f;
            line-height: 1.5;
        }

        .pending-banner .badge-pending {
            background: #f59e0b;
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: var(--radius-full);
            letter-spacing: 0.8px;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3);
        }

        /* ══════════════════════════════════════════════════════════
           6. MODAL DESIGN SYSTEM
           ══════════════════════════════════════════════════════════ */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.15), 0 10px 10px -5px rgba(15, 23, 42, 0.05);
            overflow: hidden;
            background-color: #ffffff;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--brand-green-dark) 0%, var(--brand-green) 100%) !important;
            color: #ffffff !important;
            padding: 16px 22px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header.modal-header-warning {
            background: linear-gradient(135deg, #b45309 0%, #d97706 100%) !important;
            color: #ffffff !important;
        }

        .modal-header.modal-header-danger {
            background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%) !important;
            color: #ffffff !important;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff !important;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.3px;
            margin: 0;
        }

        .modal-title i {
            color: #fbbf24 !important;
            font-size: 1.25rem;
        }

        .modal-header .btn-close,
        .modal-header .btn-close-white {
            filter: brightness(0) invert(1);
            opacity: 0.85;
            transition: opacity 0.15s ease, transform 0.15s ease;
            box-shadow: none !important;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 22px 24px;
            background-color: #ffffff;
            color: var(--slate-800);
        }

        .modal-footer {
            padding: 14px 24px;
            background-color: var(--slate-50);
            border-top: 1px solid var(--slate-200);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        /* ══════════════════════════════════════════════════════════
           7. BUTTON DESIGN SYSTEM
           ══════════════════════════════════════════════════════════ */
        .btn {
            font-weight: 600;
            border-radius: var(--radius-md);
            padding: 8px 18px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition-smooth);
            line-height: 1.5;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: 0.8125rem;
            border-radius: var(--radius-sm);
            gap: 6px;
        }

        /* Primary Brand Button (Emerald) */
        .btn-success {
            background-color: var(--brand-green);
            border-color: var(--brand-green);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(22, 91, 51, 0.18);
        }

        .btn-success:hover, .btn-success:focus {
            background-color: var(--brand-green-hover);
            border-color: var(--brand-green-hover);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px -2px rgba(22, 91, 51, 0.35);
        }

        .btn-success:active {
            background-color: var(--brand-green-dark);
            border-color: var(--brand-green-dark);
            transform: translateY(0);
        }

        /* Secondary Neutral Button (Clean Slate) */
        .btn-secondary {
            background-color: #ffffff;
            border-color: var(--slate-300);
            color: var(--slate-700);
            box-shadow: var(--shadow-xs);
        }

        .btn-secondary:hover, .btn-secondary:focus {
            background-color: var(--slate-100);
            border-color: var(--slate-400);
            color: var(--slate-900);
            transform: translateY(-1px);
        }

        .btn-secondary:active {
            background-color: var(--slate-200);
            border-color: var(--slate-400);
            transform: translateY(0);
        }

        /* Danger Button (Red) */
        .btn-danger {
            background-color: var(--brand-danger);
            border-color: var(--brand-danger);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(220, 38, 38, 0.18);
        }

        .btn-danger:hover, .btn-danger:focus {
            background-color: #b91c1c;
            border-color: #b91c1c;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px -2px rgba(220, 38, 38, 0.35);
        }

        /* Warning Button (Gold) */
        .btn-warning {
            background-color: var(--brand-gold);
            border-color: var(--brand-gold);
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(217, 119, 6, 0.18);
        }

        .btn-warning:hover, .btn-warning:focus {
            background-color: var(--brand-gold-hover);
            border-color: var(--brand-gold-hover);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px -2px rgba(217, 119, 6, 0.35);
        }

        /* Outline Buttons */
        .btn-outline-success {
            color: var(--brand-green);
            border: 1.5px solid var(--brand-green);
            background-color: transparent;
        }

        .btn-outline-success:hover, .btn-outline-success:focus {
            background-color: var(--brand-green);
            border-color: var(--brand-green);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px -2px rgba(22, 91, 51, 0.25);
        }

        .btn-outline-warning {
            color: var(--brand-gold-hover);
            border: 1.5px solid var(--brand-gold);
            background-color: transparent;
        }

        .btn-outline-warning:hover, .btn-outline-warning:focus {
            background-color: var(--brand-gold);
            border-color: var(--brand-gold);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-outline-danger {
            color: var(--brand-danger);
            border: 1.5px solid var(--brand-danger);
            background-color: transparent;
        }

        .btn-outline-danger:hover, .btn-outline-danger:focus {
            background-color: var(--brand-danger);
            border-color: var(--brand-danger);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Action Buttons in Data Tables & Cards */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            transition: var(--transition-smooth);
            border: 1px solid var(--slate-200);
            background-color: #ffffff;
            color: var(--slate-600);
            cursor: pointer;
            box-shadow: var(--shadow-xs);
        }

        .btn-action:hover {
            background-color: var(--slate-100);
            color: var(--slate-900);
            border-color: var(--slate-300);
            transform: translateY(-1px);
        }

        .btn-action.btn-action-primary:hover,
        .btn-action.btn-action-view:hover,
        .btn-action.btn-action-edit:hover {
            background-color: var(--brand-green-light);
            color: var(--brand-green);
            border-color: rgba(22, 91, 51, 0.25);
        }

        .btn-action.btn-action-danger:hover {
            background-color: var(--brand-danger-light);
            color: var(--brand-danger);
            border-color: rgba(220, 38, 38, 0.25);
        }

        .btn-action.btn-action-harvest {
            background-color: var(--brand-green-light);
            color: var(--brand-green);
            border-color: rgba(22, 91, 51, 0.25);
        }

        .btn-action.btn-action-harvest:hover {
            background-color: var(--brand-green);
            color: #ffffff;
            border-color: var(--brand-green);
            transform: translateY(-1px);
        }

        /* ══════════════════════════════════════════════════════════
           8. PAGE HEADER CONSISTENCY
           ══════════════════════════════════════════════════════════ */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-header-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.4px;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header-title i {
            color: var(--brand-green);
        }

        .page-header-desc {
            font-size: 0.85rem;
            color: var(--slate-500);
            margin-bottom: 0;
            max-width: 650px;
            line-height: 1.4;
        }

        .badge-count {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: var(--radius-full);
            background-color: var(--slate-100);
            color: var(--slate-700);
            border: 1px solid var(--slate-200);
            white-space: nowrap;
        }

        /* ══════════════════════════════════════════════════════════
           8b. PAGINATION — matches CROPS design system
           ══════════════════════════════════════════════════════════ */
        .crops-pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-top: 16px;
            margin-top: 4px;
            flex-wrap: wrap;
        }

        .crops-pagination-info {
            font-size: 12px;
            color: var(--slate-500);
            font-weight: 500;
        }

        .crops-pagination-info strong {
            color: var(--slate-800);
            font-weight: 700;
        }

        .crops-pagination .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .crops-pagination .page-item {
            display: inline-block;
        }

        .crops-pagination .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--slate-200);
            background: #ffffff;
            color: var(--slate-700);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-smooth);
            box-shadow: var(--shadow-xs);
        }

        .crops-pagination .page-link:hover:not(.disabled) {
            background: var(--brand-green-light);
            border-color: var(--brand-green);
            color: var(--brand-green-dark);
            transform: translateY(-1px);
        }

        .crops-pagination .page-item.active .page-link {
            background: var(--brand-green);
            border-color: var(--brand-green);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(22, 91, 51, 0.25);
            cursor: default;
        }

        .crops-pagination .page-item.disabled .page-link {
            background: var(--slate-50);
            color: var(--slate-300);
            border-color: var(--slate-200);
            cursor: not-allowed;
            box-shadow: none;
        }

        .crops-pagination .page-link i {
            font-size: 11px;
            line-height: 1;
        }

        /* ══════════════════════════════════════════════════════════
           9. RESPONSIVE BREAKPOINTS
           ══════════════════════════════════════════════════════════ */
        /* Tablets and below (< 992px) */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: min(290px, 85vw);
                max-width: 85vw;
                box-shadow: none;
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-lg);
            }

            .sidebar-close-btn {
                display: inline-flex;
            }

            .sidebar-footer {
                padding-bottom: calc(18px + max(env(safe-area-inset-bottom, 0px), 12px));
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-nav-toggle {
                display: inline-flex;
            }

            .topbar {
                padding: 0 20px;
            }

            .main-content {
                padding: 20px 20px 36px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pending-banner {
                flex-direction: column;
                align-items: flex-start;
            }

            .pending-banner .badge-pending {
                align-self: flex-start;
                margin-top: 6px;
            }
        }

        /* Mobile Phones (< 576px) */
        @media (max-width: 575.98px) {
            :root {
                --topbar-height: 64px;
            }

            .sidebar {
                width: min(300px, 86vw);
                max-width: 86vw;
            }

            .sidebar-footer {
                padding: 12px 14px;
                padding-bottom: calc(20px + max(env(safe-area-inset-bottom, 0px), 14px));
            }

            .sidebar-logout-btn {
                width: 40px;
                height: 40px;
                min-width: 40px;
                min-height: 40px;
                font-size: 1.15rem;
            }

            .topbar {
                padding: 0 14px;
            }

            .topbar-title {
                font-size: 1.05rem;
            }

            .user-chip-meta {
                display: none;
            }

            .user-chip {
                padding: 4px;
            }

            .main-content {
                padding: 16px 12px 32px;
            }

            .card-custom {
                padding: 18px 16px;
                border-radius: var(--radius-md);
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .stat-card {
                padding: 16px 18px;
            }

            .stat-card .stat-info .value {
                font-size: 1.5rem;
            }

            /* Pagination collapses cleanly on mobile */
            .crops-pagination-wrap {
                justify-content: center;
                padding-top: 12px;
            }

            .crops-pagination-info {
                order: 2;
                width: 100%;
                text-align: center;
                padding-top: 4px;
            }

            .crops-pagination {
                order: 1;
            }

            .crops-pagination .page-link {
                min-width: 30px;
                height: 30px;
                padding: 0 8px;
                font-size: 12px;
            }
        }

        /* Print Mode */
        @media print {
            .sidebar, .topbar, .sidebar-backdrop, .mobile-nav-toggle {
                display: none !important;
            }
            .main-wrapper {
                margin-left: 0 !important;
            }
            .main-content {
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

    @php
        $user = auth()->user();
        $role = $user->role ?? 'guest';
        $isAdmin  = $role === 'admin';
        $isStaff  = $role === 'staff';
        $isFarmer = $role === 'farmer';

        // Display user role label
        $roleLabel = match ($role) {
            'admin'  => 'CAO Admin',
            'staff'  => 'CAO Staff',
            'farmer' => 'Registered Farmer',
            default  => 'Guest User',
        };

        // Dashboard URL and route mapping
        $dashboardUrl = match ($role) {
            'admin'  => '/admin/dashboard',
            'staff'  => '/staff/dashboard',
            'farmer' => '/farmer/dashboard',
            default  => '/login',
        };
        $dashboardRoute = match ($role) {
            'admin'  => 'admin.dashboard',
            'staff'  => 'staff.dashboard',
            'farmer' => 'farmer.dashboard',
            default  => 'login',
        };

        $userInitials = $user && $user->name 
            ? strtoupper(mb_substr($user->name, 0, 1)) 
            : 'U';
    @endphp

    <!-- Mobile Drawer Overlay Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()" aria-hidden="true"></div>

    <!-- ══════════════════════════════════════════════════════════
         SIDEBAR NAVIGATION
         ══════════════════════════════════════════════════════════ -->
    <aside class="sidebar" id="sidebar" aria-label="Main Navigation">
        <!-- Sidebar Brand -->
        <div class="sidebar-brand">
            <a href="{{ $dashboardUrl }}" class="sidebar-brand-link">
                <img src="{{ asset('images/logo.webp') }}" alt="CROPS Logo">
                <div class="sidebar-brand-text">
                    <span class="brand-agency">Santiago City</span>
                    <h1>CR<span>OPS</span></h1>
                    <span class="brand-tagline">City Agriculture Office</span>
                </div>
            </a>
            <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" onclick="closeSidebar()" aria-label="Close navigation menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">

            {{-- ═══════════════ FARMER NAVIGATION ═══════════════ --}}
            @if($isFarmer)
                <div class="nav-label">My Farm</div>

                <a href="{{ $dashboardUrl }}" class="{{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('farmer.farms.index') }}" class="{{ request()->routeIs('farmer.farms.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span>My Farms</span>
                </a>

                <a href="{{ route('farmer.farm-records.index') }}" class="{{ request()->routeIs('farmer.farm-records.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span>My Seasons</span>
                </a>

                <a href="{{ route('farmer.predictions.index') }}" class="{{ request()->routeIs('farmer.predictions.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>My Predictions</span>
                </a>

                <a href="{{ route('farmer.advisories.index') }}" class="{{ request()->routeIs('farmer.advisories.*') ? 'active' : '' }}">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Advisories</span>
                </a>

                <a href="{{ route('farmer.rice-varieties.index') }}" class="{{ request()->routeIs('farmer.rice-varieties.*') ? 'active' : '' }}">
                    <i class="bi bi-flower1"></i>
                    <span>Rice Varieties</span>
                </a>
                
                <div class="nav-label">Account</div>

                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>My Profile</span>
                </a>

                <button type="button" class="sidebar-nav-btn" onclick="showLogoutModal()">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </button>
            @endif

            {{-- ═══════════════ ADMIN & STAFF NAVIGATION ═══════════════ --}}
            @if($isAdmin || $isStaff)
                <div class="nav-label">Main</div>

                <a href="{{ $dashboardUrl }}" class="{{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.predictions.index') }}" class="{{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Predictions</span>
                </a>

                <a href="{{ route('admin.rice-varieties.index') }}" class="{{ request()->routeIs('admin.rice-varieties.*') ? 'active' : '' }}">
                    <i class="bi bi-flower1"></i>
                    <span>Rice Varieties</span>
                </a>

                <div class="nav-label">People</div>

                @if($isAdmin)
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Staff Accounts</span>
                    </a>
                @endif

                <a href="{{ route('admin.farmers.index') }}" class="{{ request()->routeIs('admin.farmers.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Farmers List</span>
                    @php
                        $pendingCount = \App\Models\User::where('role', 'farmer')->whereNull('verified_by_cao_at')->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="badge bg-warning text-dark ms-auto" title="{{ $pendingCount }} pending verification">{{ $pendingCount }}</span>
                    @endif
                </a>

                <div class="nav-label">Land &amp; Records</div>

                <a href="{{ route('admin.map') }}" class="{{ request()->routeIs('admin.map') ? 'active' : '' }}">
                    <i class="bi bi-map-fill"></i>
                    <span>Farms &amp; Map</span>
                </a>

                @if($isAdmin)
                    <a href="{{ route('admin.farms.index') }}" class="{{ request()->routeIs('admin.farms.*') ? 'active' : '' }}">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span>Manage Farms</span>
                    </a>
                @endif

                <a href="{{ route('admin.farm-records.index') }}" class="{{ request()->routeIs('admin.farm-records.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span>Farm Records</span>
                </a>

                <div class="nav-label">Alerts &amp; Reports</div>


                <a href="{{ route('admin.advisories.index') }}" class="{{ request()->routeIs('admin.advisories.*') ? 'active' : '' }}">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Advisories</span>
                </a>

                @if($isAdmin)
                    <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                @endif

                <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reports</span>
                </a>
<!--
                                                @if($isAdmin)
                    <div class="nav-label">Machine Learning</div>

                    <a href="{{ route('admin.ml.index') }}" class="{{ request()->routeIs('admin.ml.*') ? 'active' : '' }}">
                        <i class="bi bi-cpu-fill"></i>
                        <span>Model Training</span>
                    </a>
                @endif
-->
                <div class="nav-label">Account</div>

                <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i>
                    <span>My Profile</span>
                </a>

                <button type="button" class="sidebar-nav-btn" onclick="showLogoutModal()">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </button>
            @endif

        </nav>

        <!-- Sidebar Footer with User Profile and Logout Button -->
        <div class="sidebar-footer">
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar">
                    {{ $userInitials }}
                </div>
                <div class="sidebar-user-details">
                    <span class="sidebar-user-name" title="{{ $user->name ?? 'User' }}">{{ $user->name ?? 'User' }}</span>
                    <span class="sidebar-user-role">
                        <i class="bi bi-shield-check"></i> {{ $roleLabel }}
                    </span>
                </div>
            </div>

            <!-- POST Logout Form (CSRF-protected) -->
            <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="sidebar-footer-form">
                @csrf
                <button type="button"
                        class="sidebar-logout-btn"
                        title="Sign out"
                        aria-label="Sign out"
                        onclick="showLogoutModal()">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ══════════════════════════════════════════════════════════
         MAIN CONTENT AREA
         ══════════════════════════════════════════════════════════ -->
    <div class="main-wrapper">
        <!-- Top Sticky Navigation Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-nav-toggle" id="sidebarToggleBtn" onclick="toggleSidebar()" aria-label="Toggle navigation drawer" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-title-wrap">
                    <h2 class="topbar-title">
                        <i class="bi bi-tree-fill"></i>
                        <span>@yield('title', 'Dashboard')</span>
                    </h2>
                </div>
            </div>

            <div class="topbar-right">
                <a href="{{ route('profile.edit') }}" class="user-chip" title="View Profile">
                    <div class="user-chip-avatar">
                        {{ $userInitials }}
                    </div>
                    <div class="user-chip-meta">
                        <span class="user-chip-name">{{ $user->name ?? 'Guest' }}</span>
                        <span class="user-chip-role">{{ $role }}</span>
                    </div>
                </a>
            </div>
        </header>

        <!-- Page Dynamic Body Content -->
        <main class="main-content">
            <div class="content-container">

                <!-- ═══ System Flash Message Banners ═══ -->
                <div class="alert-container">
                    @if(session('success'))
                        <div class="alert-custom alert-custom-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill alert-icon"></i>
                            <div class="alert-content">
                                <strong>Success!</strong> {{ session('success') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert-custom alert-custom-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                            <div class="alert-content">
                                <strong>Notice:</strong> {{ session('warning') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert-custom alert-custom-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-x-circle-fill alert-icon"></i>
                            <div class="alert-content">
                                <strong>Attention Required:</strong> {{ session('error') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('verification_required'))
                        <div class="alert-custom alert-custom-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-shield-lock-fill alert-icon"></i>
                            <div class="alert-content">
                                <strong>Verification Needed:</strong> {{ session('verification_required') }}
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>

                <!-- ═══ Pending Farmer Account Notice ═══ -->
                @if($isFarmer && !$user->verified_by_cao_at)
                    <section class="pending-banner" aria-live="polite">
                        <div class="pending-banner-left">
                            <i class="bi bi-shield-exclamation icon"></i>
                            <div class="body">
                                <strong>Your account is currently awaiting CAO Verification</strong>
                                <p>
                                    You can review existing records and overview information. Recording new farm seasons,
                                    modifying farm boundaries, and running yield predictions will become active as soon as the
                                    City Agriculture Office verifies your registered profile.
                                </p>
                            </div>
                        </div>
                        <span class="badge-pending">VERIFICATION PENDING</span>
                    </section>
                @endif

                <!-- Yield Page View Content -->
                @yield('content')

            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Responsive Viewport Height Handling (Fix for mobile browsers & Messenger in-app browser)
        function updateAppViewportHeight() {
            const vh = window.visualViewport ? window.visualViewport.height : window.innerHeight;
            document.documentElement.style.setProperty('--app-height', `${vh}px`);
        }
        window.addEventListener('resize', updateAppViewportHeight);
        window.addEventListener('orientationchange', updateAppViewportHeight);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', updateAppViewportHeight);
        }
        updateAppViewportHeight();

        // Responsive Drawer Navigation Logic
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        function openSidebar() {
            updateAppViewportHeight();
            sidebar.classList.add('open');
            backdrop.classList.add('active');
            toggleBtn?.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('active');
            toggleBtn?.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }

        function toggleSidebar() {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        // Close mobile drawer when pressing Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Auto-close drawer if window resized past mobile breakpoint
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        function showLogoutModal() {
            if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
            const modalEl = document.getElementById('logoutModal');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }

        function confirmLogout() {
            const form = document.getElementById('logoutForm');
            if (form) {
                form.submit();
            }
        }
    </script>
    @stack('scripts')

    <!-- Logout confirmation modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-box-arrow-right" style="font-size: 32px; color: var(--brand-danger);"></i>
                    <h6 id="logoutModalLabel" style="font-weight: 700; color: var(--slate-900); margin: 12px 0 6px;">
                        Sign out?
                    </h6>
                    <p style="font-size: 13px; color: var(--slate-500); margin: 0;">
                        You'll need to log in again to continue.
                    </p>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmLogout()">
                        <i class="bi bi-box-arrow-right"></i> Sign out
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>