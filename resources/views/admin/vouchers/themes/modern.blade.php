<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>قسائم الإنترنت - مودرن</title>
    <style>
        @page { margin: 0.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; background: #f5f5f5; }
        .card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; padding: 15px; margin: 8px; width: 220px;
            display: inline-block; text-align: center; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .card h3 { margin: 0 0 10px; font-size: 16px; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 8px; }
        .card .label { font-size: 10px; opacity: 0.8; }
        .card .cred { font-size: 20px; font-weight: bold; letter-spacing: 3px; margin: 2px 0 8px; word-break: break-all; }
        .card .profile { font-size: 11px; opacity: 0.9; margin-top: 5px; }
    </style>
</head>
<body>
    @foreach($cards as $card)
    <div class="card">
        <h3>{{ $network_name ?? 'شبكتي' }}</h3>
        <div class="label">اسم المستخدم</div>
        <div class="cred">{{ $card['username'] }}</div>

        <div class="profile">{{ $card['profile'] }}</div>
        @if(!empty($card['expires_at']))
        <div class="profile">صالح حتى: {{ $card['expires_at'] }}</div>
        @endif
    </div>
    @endforeach
</body>
</html>
