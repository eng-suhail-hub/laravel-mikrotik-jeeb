<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>قسائم الإنترنت</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'DejaVu Sans', sans-serif; }
        .card { border: 2px solid #333; padding: 10px; margin: 5px; width: 200px; display: inline-block; text-align: center; }
        .card h3 { margin: 0 0 5px; font-size: 14px; }
        .card .cred { font-size: 18px; font-weight: bold; letter-spacing: 2px; }
        .card .profile { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    @foreach($cards as $card)
    <div class="card">
        <h3>{{ $network_name ?? 'شبكتي' }}</h3>
        <div>اسم المستخدم</div>
        <div class="cred">{{ $card['username'] }}</div>
        <div>كلمة المرور</div>
        <div class="cred">{{ $card['password'] }}</div>
        <div class="profile">{{ $card['profile'] }}</div>
        @if(!empty($card['expires_at']))
        <div class="profile">صالح حتى: {{ $card['expires_at'] }}</div>
        @endif
    </div>
    @endforeach
</body>
</html>
