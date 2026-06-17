@extends('admin.layouts.app')

@section('title', 'إعدادات الراوتر')

@section('content')
<h2 class="mb-4"><i class="bi bi-router"></i> إعدادات الراوتر (MikroTik v6)</h2>

@if($setting->is_connected && $setting->routeros_version)
    <div class="alert alert-success">
        <h5><i class="bi bi-check-circle-fill"></i> الراوتر متصل</h5>
        <hr>
        <div class="row">
            <div class="col-md-4"><strong>الهوية:</strong> {{ $setting->router_identity }}</div>
            <div class="col-md-4"><strong>الإصدار:</strong> {{ $setting->routeros_version }}</div>
            <div class="col-md-4"><strong>اللوحة:</strong> {{ $setting->board_name }}</div>
        </div>
        <small class="text-muted">آخر اختبار: {{ $setting->last_test_at?->diffForHumans() }}</small>
    </div>
@elseif($setting->host !== '0.0.0.0')
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        البيانات محفوظة لكن الاتصال غير ناجح. أعد اختبار الاتصال.
    </div>
@endif

@if($errors->has('connection'))
    <div class="alert alert-danger">
        <strong>فشل الاتصال:</strong> {{ $errors->first('connection') }}
    </div>
@endif

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">بيانات الاتصال</h5>
        <small class="text-muted">أدخل بيانات المايكروتك لاختبار الاتصال وحفظ الإعدادات</small>
    </div>
    <div class="card-body">
        <form id="routerForm" method="POST" action="{{ route('admin.router.connect') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">عنوان IP <span class="text-danger">*</span></label>
                    <input type="text" name="host" class="form-control" required
                           placeholder="192.168.88.1"
                           value="{{ old('host', $setting->host) }}">
                    <small class="text-muted">مثال: 192.168.88.1</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">المنفذ <span class="text-danger">*</span></label>
                    <input type="number" name="port" class="form-control" required min="1" max="65535"
                           value="{{ old('port', $setting->port ?? 8728) }}">
                    <small class="text-muted">8728 افتراضياً</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required
                           value="{{ old('username', $setting->username) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required
                           placeholder="{{ $setting->password ? '•••••••• (محفوظة)' : 'أدخل كلمة مرور الراوتر' }}">
                    <small class="text-muted">⚠️ تُحفظ مُشفّرة في قاعدة البيانات</small>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="button" id="btnTest" class="btn btn-outline-primary">
                    <i class="bi bi-wifi"></i> اختبار الاتصال فقط
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> حفظ + اختبار + تفعيل
                </button>
            </div>
        </form>

        {{-- نتيجة الاختبار (AJAX) --}}
        <div id="testResult" class="mt-3" style="display:none"></div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <h6><i class="bi bi-info-circle"></i> ملاحظات هامة</h6>
        <ul class="mb-0 small text-muted">
            <li>الاتصال عبر منفذ <strong>8728</strong> (RouterOS API v6) — تأكد من تفعيله في الراوتر عبر WinBox: <code>IP → Services → api</code></li>
            <li>كلمة المرور تُحفظ مُشفّرة (Laravel encrypted cast) ولا تظهر في الواجهة مرة أخرى</li>
            <li>بعد نجاح الاتصال، يصبح النظام جاهزاً لتوليد الكروت</li>
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
    resultBox.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> جاري الاختبار...</div>';

    try {
        const response = await fetch('{{ route("admin.router.test") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();

        if (data.success) {
            resultBox.innerHTML = `
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle-fill"></i> نجح الاتصال!</h6>
                    <ul class="mb-0">
                        <li><strong>الهوية:</strong> ${data.info.identity}</li>
                        <li><strong>الإصدار:</strong> ${data.info.version}</li>
                        <li><strong>اللوحة:</strong> ${data.info.board}</li>
                    </ul>
                </div>`;
        } else {
            resultBox.innerHTML = `<div class="alert alert-danger"><strong>فشل:</strong> ${data.error}</div>`;
        }
    } catch (err) {
        resultBox.innerHTML = `<div class="alert alert-danger">خطأ في الشبكة: ${err.message}</div>`;
    }
});
</script>
@endpush
