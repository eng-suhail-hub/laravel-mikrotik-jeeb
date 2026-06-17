<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — {{ config('app.name') }}</title>
    {{-- Bootstrap 5 (RTL) عبر CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family: 'Tahoma', 'Segoe UI', sans-serif; background: #f4f6f9; }
        .sidebar { background: #1e3a5f; min-height: 100vh; }
        .sidebar a { color: #cfd8dc; text-decoration: none; padding: 12px 20px; display: block; border-right: 3px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: #0d2a4a; color: #fff; border-right-color: #4fc3f7; }
        .status-badge { font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        {{-- Sidebar --}}
        <nav class="col-md-3 col-lg-2 sidebar p-0">
            <div class="p-3 text-white">
                <h5 class="mb-0"><i class="bi bi-hdd-network"></i> MikroTik Cards</h5>
                <small class="text-muted">لوحة تحكم المسؤول</small>
            </div>
            <hr class="text-light">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> لوحة المعلومات
            </a>
            <a href="{{ route('admin.router.index') }}" class="{{ request()->routeIs('admin.router.*') ? 'active' : '' }}">
                <i class="bi bi-router"></i> إعدادات الراوتر
            </a>
            <a href="{{ route('admin.profiles.index') }}" class="{{ request()->routeIs('admin.profiles.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> الباقات
            </a>
            <a href="{{ route('admin.transactions.pending') }}" class="{{ request()->routeIs('admin.transactions.pending*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i> العمليات المعلقة
                @php $pendingCount = \App\Models\Transaction::pending()->count(); @endphp
                @if($pendingCount > 0)
                    <span class="badge bg-warning text-dark">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="{{ request()->routeIs('admin.transactions.index') || request()->routeIs('admin.transactions.show') ? 'active' : '' }}">
                <i class="bi bi-list-check"></i> كل العمليات
            </a>
            <hr class="text-light">
            <div class="px-3 mt-4 text-light">
                <small>{{ Auth::guard('admin')->user()->full_name ?? Auth::guard('admin')->user()->username }}</small>
                <form action="{{ route('admin.logout') }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">
                        <i class="bi bi-box-arrow-right"></i> خروج
                    </button>
                </form>
            </div>
        </nav>

        {{-- Main content --}}
        <main class="col-md-9 col-lg-10 p-4">
            {{-- رسائل الفلاش --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any() && !$errors->has('connection'))
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
