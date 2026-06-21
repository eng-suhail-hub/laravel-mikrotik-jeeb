<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy-950: #0b1219;
            --navy-900: #111d2b;
            --navy-800: #17283b;
            --navy-700: #1e3450;
            --accent: #0066b1;
            --accent-light: #0088e6;
            --accent-glow: rgba(0, 102, 177, 0.3);
            --m-red: #e4002b;
            --m-blue-dark: #1c69d4;
            --success: #16a34a;
            --warning: #f59e0b;
            --danger: #e4002b;
            --bg: #f0f4f9;
            --surface: #ffffff;
            --fg: #111827;
            --muted: #64748b;
            --border: #e2e8f0;
            --sidebar-w: 260px;
            --header-h: 64px;
            --radius: 10px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Tahoma', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--fg);
            overflow-x: hidden;
            min-height: 100vh;
        }

        ::selection { background: var(--accent); color: #fff; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--muted); }

        .sidebar {
            position: fixed;
            top: 0; right: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: linear-gradient(180deg, var(--navy-950) 0%, var(--navy-900) 100%);
            z-index: 1040;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 1px solid rgba(255,255,255,0.04);
        }

        .sidebar-brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            position: relative;
            overflow: hidden;
        }

        .sidebar-brand::after {
            content: '';
            position: absolute;
            bottom: 0; right: 20px;
            width: calc(100% - 40px);
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--m-red), var(--accent));
            background-size: 200% 100%;
            animation: stripeShimmer 4s linear infinite;
        }

        @keyframes stripeShimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .sidebar-brand .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--m-blue-dark));
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .sidebar-brand h5 {
            font-weight: 700;
            font-size: 15px;
            letter-spacing: -0.01em;
            margin: 0;
        }

        .sidebar-brand small {
            font-size: 11px;
            font-weight: 400;
            opacity: 0.5;
            letter-spacing: 0.3px;
        }

        .sidebar-nav { padding: 12px 10px; }

        .sidebar-nav .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.25);
            padding: 16px 12px 6px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            margin: 2px 0;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .sidebar-nav a i {
            width: 22px;
            text-align: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-nav a .badge {
            margin-right: auto;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
        }

        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
            transform: translateX(-2px);
        }

        .sidebar-nav a.active {
            background: linear-gradient(135deg, rgba(0,102,177,0.2) 0%, rgba(0,102,177,0.08) 100%);
            color: #fff;
            border: 1px solid rgba(0,102,177,0.25);
            box-shadow: 0 0 20px rgba(0,102,177,0.08);
        }

        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            right: -10px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: var(--accent);
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 10px var(--accent-glow);
        }

        .sidebar-nav hr {
            margin: 10px 0;
            border-color: rgba(255,255,255,0.06);
        }

        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-footer .admin-name {
            font-size: 13px;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
        }

        .sidebar-footer .admin-role {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
        }

        .sidebar-footer .btn-logout {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
            width: 100%;
            cursor: pointer;
        }

        .sidebar-footer .btn-logout:hover {
            background: rgba(228,0,43,0.15);
            border-color: rgba(228,0,43,0.3);
            color: #ff4d4d;
        }

        .main-wrapper {
            margin-right: var(--sidebar-w);
            min-height: 100vh;
            transition: margin 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .topbar {
            height: var(--header-h);
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1030;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-left .breadcrumb {
            margin: 0;
            background: none;
            font-size: 13px;
        }

        .topbar-left .breadcrumb-item + .breadcrumb-item::before {
            content: '/';
            color: var(--muted);
            font-weight: 300;
        }

        .topbar-left .breadcrumb-item a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
        }

        .topbar-left .breadcrumb-item.active {
            color: var(--fg);
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-btn {
            width: 36px; height: 36px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }

        .topbar-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(0,102,177,0.05);
        }

        .topbar-btn .dot {
            position: absolute;
            top: 6px; left: 6px;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--m-red);
            box-shadow: 0 0 6px rgba(228,0,43,0.4);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 12px 4px 4px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .topbar-user:hover { background: rgba(0,0,0,0.03); }

        .topbar-user .avatar {
            width: 32px; height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), var(--m-blue-dark));
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        .topbar-user .user-info { line-height: 1.2; }
        .topbar-user .user-info .name { font-size: 13px; font-weight: 600; }
        .topbar-user .user-info .role { font-size: 11px; color: var(--muted); }

        .page-content {
            padding: 24px 28px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-weight: 700;
            font-size: 22px;
            letter-spacing: -0.02em;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: var(--muted);
            font-size: 14px;
            margin: 4px 0 0;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 16px 20px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body { padding: 20px; }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: default;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.08);
        }

        .stat-card .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .stat-card .stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .stat-card .stat-footer {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        .stat-card .stat-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 20%, var(--glow-color, rgba(0,102,177,0.06)) 0%, transparent 60%);
            pointer-events: none;
        }

        .stat-card.accent-blue { --glow-color: rgba(0,102,177,0.08); border-top: 3px solid var(--accent); }
        .stat-card.accent-green { --glow-color: rgba(22,163,74,0.08); border-top: 3px solid var(--success); }
        .stat-card.accent-red { --glow-color: rgba(228,0,43,0.08); border-top: 3px solid var(--m-red); }
        .stat-card.accent-amber { --glow-color: rgba(245,158,11,0.08); border-top: 3px solid var(--warning); }

        .stat-card.accent-blue .stat-icon { background: rgba(0,102,177,0.1); color: var(--accent); }
        .stat-card.accent-green .stat-icon { background: rgba(22,163,74,0.1); color: var(--success); }
        .stat-card.accent-red .stat-icon { background: rgba(228,0,43,0.1); color: var(--m-red); }
        .stat-card.accent-amber .stat-icon { background: rgba(245,158,11,0.1); color: var(--warning); }

        .table-clean {
            width: 100%;
            border-collapse: collapse;
        }

        .table-clean thead th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            text-align: start;
            white-space: nowrap;
        }

        .table-clean tbody td {
            padding: 12px 16px;
            font-size: 13.5px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .table-clean tbody tr {
            transition: background 0.15s;
        }

        .table-clean tbody tr:hover {
            background: rgba(0,102,177,0.02);
        }

        .table-clean tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-status {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-status::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-status.bg-success { background: rgba(22,163,74,0.1); color: #15803d; }
        .badge-status.bg-success::before { background: #16a34a; }
        .badge-status.bg-danger { background: rgba(228,0,43,0.1); color: #b91c1c; }
        .badge-status.bg-danger::before { background: var(--m-red); }
        .badge-status.bg-warning { background: rgba(245,158,11,0.1); color: #b45309; }
        .badge-status.bg-warning::before { background: var(--warning); }
        .badge-status.bg-secondary { background: rgba(100,116,139,0.1); color: #475569; }
        .badge-status.bg-secondary::before { background: var(--muted); }
        .badge-status.bg-info { background: rgba(0,102,177,0.1); color: var(--accent); }
        .badge-status.bg-info::before { background: var(--accent); }

        .btn-m {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
        }

        .btn-m-primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
        .btn-m-primary:hover {
            background: var(--accent-light);
            border-color: var(--accent-light);
            color: #fff;
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .btn-m-outline {
            background: transparent;
            color: var(--fg);
            border-color: var(--border);
        }
        .btn-m-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(0,102,177,0.04);
        }

        .btn-m-success {
            background: var(--success);
            color: #fff;
        }
        .btn-m-success:hover {
            background: #15803d;
            box-shadow: 0 4px 14px rgba(22,163,74,0.3);
        }

        .btn-m-danger {
            background: transparent;
            color: var(--m-red);
            border-color: rgba(228,0,43,0.2);
        }
        .btn-m-danger:hover {
            background: rgba(228,0,43,0.06);
            border-color: rgba(228,0,43,0.3);
        }

        .btn-m-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-m-icon {
            width: 32px; height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
        }

        .form-control-m {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13.5px;
            font-family: inherit;
            background: var(--surface);
            color: var(--fg);
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
        }

        .form-control-m:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0,102,177,0.1);
            outline: none;
        }

        .form-control-m::placeholder { color: var(--muted); }

        select.form-control-m {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            padding-left: 32px;
        }

        .alert-m {
            padding: 14px 18px;
            border-radius: var(--radius);
            font-size: 13.5px;
            border: 1px solid transparent;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-m i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .alert-m-success {
            background: rgba(22,163,74,0.06);
            border-color: rgba(22,163,74,0.15);
            color: #15803d;
        }

        .alert-m-danger {
            background: rgba(228,0,43,0.06);
            border-color: rgba(228,0,43,0.15);
            color: #b91c1c;
        }

        .alert-m-warning {
            background: rgba(245,158,11,0.06);
            border-color: rgba(245,158,11,0.15);
            color: #b45309;
        }

        .alert-m-info {
            background: rgba(0,102,177,0.06);
            border-color: rgba(0,102,177,0.15);
            color: var(--accent);
        }

        .link-m {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: color 0.2s;
        }

        .link-m:hover { color: var(--accent-light); }

        .code-m {
            font-family: 'SF Mono', ui-monospace, Menlo, monospace;
            font-size: 12.5px;
            background: rgba(0,0,0,0.04);
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--fg);
            direction: ltr;
            display: inline-block;
        }

        .modal-m .modal-content {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .modal-m .modal-header {
            border-bottom: 1px solid var(--border);
            padding: 18px 20px;
        }

        .modal-m .modal-footer {
            border-top: 1px solid var(--border);
            padding: 14px 20px;
        }

        .pagination-m {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .pagination-m a, .pagination-m span {
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            border: 1px solid var(--border);
            text-decoration: none;
            color: var(--fg);
            transition: all 0.2s;
        }

        .pagination-m a:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .pagination-m .active span {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .anim-fade-in { animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) both; }

        .anim-fade-in-d1 { animation-delay: 0.05s; }
        .anim-fade-in-d2 { animation-delay: 0.1s; }
        .anim-fade-in-d3 { animation-delay: 0.15s; }
        .anim-fade-in-d4 { animation-delay: 0.2s; }
        .anim-fade-in-d5 { animation-delay: 0.25s; }
        .anim-fade-in-d6 { animation-delay: 0.3s; }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 40px;
            opacity: 0.3;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 14px;
            margin: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-right: 0;
            }
            .page-content { padding: 16px; }
            .topbar { padding: 0 16px; }
        }

        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1039;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .sidebar-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .router-status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 6px;
        }

        .router-status-dot.online {
            background: var(--success);
            box-shadow: 0 0 8px rgba(22,163,74,0.4);
            animation: pulse-dot 2s infinite;
        }

        .router-status-dot.offline {
            background: var(--m-red);
            box-shadow: 0 0 8px rgba(228,0,43,0.4);
        }

        .sidebar-nav a .router-status-dot {
            width: 6px; height: 6px;
            margin: 0;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand d-flex align-items-center gap-3">
            <div class="logo-icon"><i class="bi bi-hdd-network"></i></div>
            <div>
                <h5 class="text-white">MikroTik Cards</h5>
                <small class="text-white-50">لوحة تحكم المسؤول</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">الرئيسية</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> لوحة المعلومات
            </a>

            <div class="nav-section">الإدارة</div>
            <a href="{{ route('admin.router.index') }}" class="{{ request()->routeIs('admin.router.*') ? 'active' : '' }}">
                <i class="bi bi-router"></i> إعدادات الراوتر
                @php $routerOnline = \App\Models\RouterSetting::current()->is_connected; @endphp
                <span class="router-status-dot {{ $routerOnline ? 'online' : 'offline' }}"></span>
            </a>
            <a href="{{ route('admin.profiles.index') }}" class="{{ request()->routeIs('admin.profiles.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> الباقات
            </a>

            <div class="nav-section">المعاملات</div>
            <a href="{{ route('admin.transactions.pending') }}" class="{{ request()->routeIs('admin.transactions.pending*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> المعلقة
                @php $pendingCount = \App\Models\Transaction::pending()->count(); @endphp
                @if($pendingCount > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="{{ request()->routeIs('admin.transactions.index') || request()->routeIs('admin.transactions.show') ? 'active' : '' }}">
                <i class="bi bi-list-check"></i> كل العمليات
            </a>

            <div class="nav-section">النقاط والتحديات</div>
            <a href="{{ route('admin.points.index') }}" class="{{ request()->routeIs('admin.points.*') ? 'active' : '' }}">
                <i class="bi bi-coin"></i> إدارة النقاط
            </a>
            <a href="{{ route('admin.challenges.index') }}" class="{{ request()->routeIs('admin.challenges.*') ? 'active' : '' }}">
                <i class="bi bi-trophy"></i> التحديات
            </a>

            <div class="nav-section">أدوات</div>
            <a href="{{ route('admin.batch.index') }}" class="{{ request()->routeIs('admin.batch.*') ? 'active' : '' }}">
                <i class="bi bi-layers"></i> التوليد الجماعي
            </a>
            <a href="{{ route('admin.vouchers.index') }}" class="{{ request()->routeIs('admin.vouchers.*') ? 'active' : '' }}">
                <i class="bi bi-printer"></i> طباعة القسائم
            </a>
            <a href="{{ route('admin.maintenance.index') }}" class="{{ request()->routeIs('admin.maintenance.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> صيانة الراوتر
            </a>

            <hr>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> إعدادات النظام
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar-sm" style="width:32px;height:32px;border-radius:6px;background:linear-gradient(135deg,var(--accent),var(--m-blue-dark));display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;flex-shrink:0;">
                    {{ mb_substr(Auth::guard('admin')->user()->full_name ?? Auth::guard('admin')->user()->username, 0, 1) }}
                </div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="admin-name text-truncate">{{ Auth::guard('admin')->user()->full_name ?? Auth::guard('admin')->user()->username }}</div>
                    <div class="admin-role">مسؤول النظام</div>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                </button>
            </form>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-btn d-md-none" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الرئيسية</a></li>
                        @if(request()->routeIs('admin.dashboard'))
                            <li class="breadcrumb-item active">لوحة المعلومات</li>
                        @elseif(request()->routeIs('admin.router.*'))
                            <li class="breadcrumb-item active">إعدادات الراوتر</li>
                        @elseif(request()->routeIs('admin.profiles.*'))
                            <li class="breadcrumb-item active">الباقات</li>
                        @elseif(request()->routeIs('admin.transactions.pending*'))
                            <li class="breadcrumb-item active">المعاملات المعلقة</li>
                        @elseif(request()->routeIs('admin.transactions.index'))
                            <li class="breadcrumb-item active">كل المعاملات</li>
                        @elseif(request()->routeIs('admin.transactions.show'))
                            <li class="breadcrumb-item active">تفاصيل المعاملة</li>
                        @elseif(request()->routeIs('admin.points.*'))
                            <li class="breadcrumb-item active">النقاط</li>
                        @elseif(request()->routeIs('admin.challenges.*'))
                            <li class="breadcrumb-item active">التحديات</li>
                        @elseif(request()->routeIs('admin.batch.*'))
                            <li class="breadcrumb-item active">التوليد الجماعي</li>
                        @elseif(request()->routeIs('admin.vouchers.*'))
                            <li class="breadcrumb-item active">طباعة القسائم</li>
                        @elseif(request()->routeIs('admin.maintenance.*'))
                            <li class="breadcrumb-item active">صيانة الراوتر</li>
                        @elseif(request()->routeIs('admin.settings.*'))
                            <li class="breadcrumb-item active">الإعدادات</li>
                        @endif
                    </ol>
                </nav>
            </div>
            <div class="topbar-right">
                <button class="topbar-btn" title="تحديث">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <div class="topbar-user">
                    <div class="avatar">
                        {{ mb_substr(Auth::guard('admin')->user()->full_name ?? Auth::guard('admin')->user()->username, 0, 1) }}
                    </div>
                    <div class="user-info d-none d-sm-block">
                        <div class="name">{{ Auth::guard('admin')->user()->full_name ?? Auth::guard('admin')->user()->username }}</div>
                        <div class="role">مسؤول</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert-m alert-m-success mb-4 anim-fade-in" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" style="margin-right:auto;background:none;border:none;cursor:pointer;opacity:0.5;font-size:18px;padding:0;line-height:1;" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert-m alert-m-warning mb-4 anim-fade-in" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('warning') }}</span>
                    <button type="button" style="margin-right:auto;background:none;border:none;cursor:pointer;opacity:0.5;font-size:18px;padding:0;line-height:1;" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert-m alert-m-danger mb-4 anim-fade-in" role="alert">
                    <i class="bi bi-x-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" style="margin-right:auto;background:none;border:none;cursor:pointer;opacity:0.5;font-size:18px;padding:0;line-height:1;" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif
            @if($errors->any() && !$errors->has('connection'))
                <div class="alert-m alert-m-danger mb-4 anim-fade-in" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <ul class="mb-0" style="list-style:none;padding:0;margin:0;">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            });
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>