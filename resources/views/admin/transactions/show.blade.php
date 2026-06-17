@extends('admin.layouts.app')
@section('title', 'تفاصيل العملية #' . $transaction->id)

@section('content')
<h2 class="mb-4">تفاصيل العملية #{{ $transaction->id }}</h2>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>الحالة الحالية</strong></div>
            <div class="card-body">
                <h4>
                    <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'warning text-dark') }}">
                        {{ $transaction->status_label }}
                    </span>
                </h4>
                @if($transaction->failure_reason)
                    <div class="alert alert-danger mt-3 mb-0">
                        <strong>سبب الفشل:</strong> {{ $transaction->failure_reason }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>بيانات الكرت</strong></div>
            <div class="card-body">
                @if($transaction->mikrotik_username)
                    <table class="table table-sm mb-0">
                        <tr><th>اسم المستخدم:</th><td><code>{{ $transaction->mikrotik_username }}</code></td></tr>
                        <tr><th>كلمة المرور:</th><td><code>{{ $transaction->mikrotik_password }}</code></td></tr>
                        <tr><th>وقت التوليد:</th><td>{{ $transaction->card_generated_at?->format('Y-m-d H:i:s') }}</td></tr>
                    </table>
                @else
                    <p class="text-muted mb-0">لم يتم توليد الكرت بعد.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>العميل</strong></div>
            <div class="card-body">
                @if($transaction->user)
                    <p class="mb-1"><strong>{{ $transaction->user->full_name }}</strong></p>
                    <p class="mb-0 text-muted">{{ $transaction->user->phone }}</p>
                @else
                    <p class="text-muted">العميل من Webhook:</p>
                    <p class="mb-1"><strong>{{ $transaction->webhook_full_name ?? '—' }}</strong></p>
                    <p class="mb-0 text-muted">{{ $transaction->webhook_phone ?? '—' }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white"><strong>بيانات الدفع</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>المبلغ:</th><td>{{ $transaction->webhook_amount ? number_format($transaction->webhook_amount, 2) . ' ر.ي' : '—' }}</td></tr>
                    <tr><th>رقم المرجع:</th><td><code>{{ $transaction->jeeb_reference ?? '—' }}</code></td></tr>
                    <tr><th>الباقة:</th><td>{{ $transaction->profile->name ?? '—' }}</td></tr>
                    <tr><th>المُفعّل:</th><td>{{ $transaction->activatedByAdmin->username ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    @if($transaction->rawWebhook)
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white"><strong>السجل الخام للإشعار (المرجع المالي)</strong></div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small mb-2" style="max-height: 200px; overflow: auto;">{{ $transaction->rawWebhook->raw_payload }}</pre>
                    <small class="text-muted">
                        استُقبل في: {{ $transaction->rawWebhook->received_at }} |
                        IP: {{ $transaction->rawWebhook->source_ip }} |
                        مُحلّل بنجاح: {{ $transaction->rawWebhook->parsed_successfully ? '✓' : '✗' }}
                    </small>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="mt-4 d-flex gap-2">
    <a href="{{ route('admin.transactions.pending') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-right"></i> العودة
    </a>
    @if(in_array($transaction->status, ['pending_match', 'manual_pending']) && $transaction->profile_id)
        <form action="{{ route('admin.transactions.activate', $transaction) }}" method="POST"
              onsubmit="return confirm('تأكيد التفعيل اليدوي؟')">
            @csrf
            <button class="btn btn-success"><i class="bi bi-play-fill"></i> تفعيل يدوي</button>
        </form>
    @endif
    @if($transaction->status === 'failed')
        <form action="{{ route('admin.transactions.retry', $transaction) }}" method="POST">
            @csrf
            <button class="btn btn-warning"><i class="bi bi-arrow-clockwise"></i> إعادة المحاولة</button>
        </form>
    @endif
</div>
@endsection
