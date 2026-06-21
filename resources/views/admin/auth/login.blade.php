<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent: #0066b1;
            --accent-light: #0088e6;
            --accent-glow: rgba(0, 102, 177, 0.25);
            --m-red: #e4002b;
            --m-blue-dark: #1c69d4;
            --navy-950: #0b1219;
            --navy-900: #111d2b;
        }

        * { box-sizing: border-box; }
        
        body {
            font-family: 'Inter', 'Tahoma', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--navy-950);
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(0,102,177,0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(228,0,43,0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(28,105,212,0.06) 0%, transparent 50%);
            pointer-events: none;
            animation: bgDrift 20s ease-in-out infinite alternate;
        }

        @keyframes bgDrift {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-2%, -2%) rotate(3deg); }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-brand .logo {
            width: 56px; height: 56px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, var(--accent), var(--m-blue-dark));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #fff;
            box-shadow: 0 8px 24px var(--accent-glow);
            position: relative;
        }

        .login-brand .logo::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--accent), var(--m-red), var(--accent));
            z-index: -1;
            opacity: 0.3;
            animation: borderPulse 3s ease-in-out infinite;
        }

        @keyframes borderPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }

        .login-brand h1 {
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin: 0 0 4px;
        }

        .login-brand p {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            font-weight: 500;
            margin: 0;
            letter-spacing: 0.3px;
        }

        .login-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.3);
        }

        .login-card .form-label {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .login-card .form-control {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .login-card .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            outline: none;
        }

        .login-card .form-control::placeholder {
            color: rgba(255,255,255,0.2);
        }

        .login-card .form-check-label {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
        }

        .login-card .form-check-input {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.15);
        }

        .login-card .form-check-input:checked {
            background: var(--accent);
            border-color: var(--accent);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--m-blue-dark));
            color: #fff;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px var(--accent-glow);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-left: 6px;
        }

        .login-error {
            background: rgba(228,0,43,0.1);
            border: 1px solid rgba(228,0,43,0.2);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ff6b6b;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
        }

        .login-footer small {
            color: rgba(255,255,255,0.2);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container anim-fade-in">
        <div class="login-brand">
            <div class="logo"><i class="bi bi-hdd-network"></i></div>
            <h1>MikroTik Cards</h1>
            <p>لوحة تحكم المسؤول</p>
        </div>

        <div class="login-card">
            @if($errors->any())
                <div class="login-error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" required autofocus
                           value="{{ old('username') }}" placeholder="أدخل اسم المستخدم">
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required
                           placeholder="أدخل كلمة المرور">
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">تذكرني</label>
                </div>
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-left"></i> دخول
                </button>
            </form>
        </div>

        <div class="login-footer">
            <small>v{{ config('app.version', '2.0') }} — نظام إدارة كروت المايكروتك</small>
        </div>
    </div>

    <style>
        .anim-fade-in {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</body>
</html>