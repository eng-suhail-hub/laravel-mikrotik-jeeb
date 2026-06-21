@extends('admin.layouts.app')

@section('title', 'إعدادات الراوتر')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-router" style="color:var(--accent)"></i> إعدادات الراوتر</h2>
    <p>إعدادات الاتصال بمخدم MikroTik v6 عبر API</p>
</div>

@if($setting->is_connected && $setting->routeros_version)
    <div class="alert-m alert-m-success mb-4 anim-fade-in">
        <i class="bi bi-check-circle-fill"></i>
        <div>
            <strong style="font-size:15px;">الراوتر متصل</strong>
            <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:16px;font-size:13px;">
                <span><strong>الهوية:</strong> {{ $setting->router_identity }}</span>
                <span><strong>الإصدار:</strong> {{ $setting->routeros_version }}</span>
                <span><strong>اللوحة:</strong> {{ $setting->board_name }}</span>
            </div>
            <div style="margin-top:4px;font-size:12px;opacity:0.7;">
                <i class="bi bi-clock"></i> آخر اختبار: {{ $setting->last_test_at?->diffForHumans() }}
            </div>
        </div>
    </div>
@elseif($setting->host !== '0.0.0.0')
    <div class="alert-m alert-m-warning mb-4 anim-fade-in">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span>البيانات محفوظة لكن الاتصال غير ناجح. يرجى إعادة اختبار الاتصال.</span>
    </div>
@endif

@if($errors->has('connection'))
    <div class="alert-m alert-m-danger mb-4 anim-fade-in">
        <i class="bi bi-x-circle-fill"></i>
        <span><strong>فشل الاتصال:</strong> {{ $errors->first('connection') }}</span>
    </div>
@endif

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-wifi" style="color:var(--accent)"></i>
        بيانات الاتصال
        <span style="margin-right:auto;font-size:12px;color:var(--muted);font-weight:400;">أدخل بيانات المايكروتك لاختبار الاتصال</span>
    </div>
    <div class="card-body">
        <form id="routerForm" method="POST" action="{{ route('admin.router.connect') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">
                        عنوان IP <span style="color:var(--m-red);">*</span>
                    </label>
                    <input type="text" name="host" class="form-control-m" required
                           placeholder="192.168.88.1"
                           value="{{ old('host', $setting->host) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">
                        المنفذ <span style="color:var(--m-red);">*</span>
                    </label>
                    <input type="number" name="port" class="form-control-m" required min="1" max="65535"
                           value="{{ old('port', $setting->port ?? 8728) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">
                        اسم المستخدم <span style="color:var(--m-red);">*</span>
                    </label>
                    <input type="text" name="username" class="form-control-m" required
                           value="{{ old('username', $setting->username) }}">
                </div>
                <div class="col-12">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">
                        كلمة المرور <span style="color:var(--m-red);">*</span>
                    </label>
                    <input type="password" name="password" class="form-control-m" required
                           placeholder="{{ $setting->password ? '•••••••• (محفوظة)' : 'أدخل كلمة مرور الراوتر' }}">
                    <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                        <i class="bi bi-shield-lock"></i> تُحفظ مُشفّرة في قاعدة البيانات
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="button" id="btnTest" class="btn-m btn-m-outline">
                    <i class="bi bi-wifi"></i> اختبار الاتصال فقط
                </button>
                <button type="submit" class="btn-m btn-m-primary">
                    <i class="bi bi-save"></i> حفظ + اختبار + تفعيل
                </button>
            </div>
        </form>

        <div id="testResult" class="mt-3" style="display:none"></div>
    </div>
</div>

<div class="card mt-3 anim-fade-in">
    <div class="card-header">
        <i class="bi bi-info-circle" style="color:var(--accent)"></i>
        ملاحظات هامة
    </div>
    <div class="card-body">
        <ul class="mb-0" style="font-size:13px;color:var(--muted);padding-right:16px;">
            <li style="margin-bottom:6px;">
                الاتصال عبر منفذ <strong style="color:var(--fg);">8728</strong>
                (RouterOS API v6) — تأكد من تفعيله في الراوتر عبر WinBox:
                <span class="code-m">IP → Services → api</span>
            </li>
            <li style="margin-bottom:6px;">
                كلمة المرور تُحفظ مُشفّرة (Laravel encrypted cast) ولا تظهر في الواجهة مرة أخرى
            </li>
            <li>
                بعد نجاح الاتصال، يصبح النظام جاهزاً لتوليد الكروت
            </li>
        </ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btnTest').addEventListener('click', async () => {
    const form = document.getElementById('routerForm');
    const formData = new FormData(form);
    const resultBox = document.getElementById('testResult');

    resultBox.style.display = 'block';
    resultBox.innerHTML =
        '<div class="alert-m alert-m-info" style="margin:0;">' +
        '<i class="bi bi-hourglass-split"></i> جاري الاختبار...</div>';

    try {
        const response = await fetch('{{ route("admin.router.test") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                    '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();

        if (data.success) {
            resultBox.innerHTML = `
                <div class="alert-m alert-m-success" style="margin:0;">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <strong>نجح الاتصال!</strong>
                        <div style="margin-top:6px;display:flex;gap:16px;font-size:13px;flex-wrap:wrap;">
                            <span><strong>الهوية:</strong> ${data.info.identity}</span>
                            <span><strong>الإصدار:</strong> ${data.info.version}</span>
                            <span><strong>اللوحة:</strong> ${data.info.board}</span>
                        </div>
                    </div>
                </div>`;
        } else {
            resultBox.innerHTML =
                `<div class="alert-m alert-m-danger" style="margin:0;"><i class="bi bi-x-circle-fill"></i> <strong>فشل:</strong> ${data.error}</div>`;
        }
    } catch (err) {
        resultBox.innerHTML =
            `<div class="alert-m alert-m-danger" style="margin:0;"><i class="bi bi-x-circle-fill"></i> خطأ في الشبكة: ${err.message}</div>`;
    }
});
</script>
@endpush