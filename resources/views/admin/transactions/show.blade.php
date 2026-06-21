@extends('admin.layouts.app')

@section('title', 'تفاصيل العملية #' . $transaction->id)

@section('content')
<div class="page-header">
    <h2><i class="bi bi-file-text" style="color:var(--accent)"></i> تفاصيل العملية #{{ $transaction->id }}</h2>
</div>

@if(session('success'))
    <div class="alert-m alert-m-success mb-4 anim-fade-in">{{ session('success') }}</div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-info-circle" style="color:var(--accent)"></i>
                الحالة الحالية
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="badge-status bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'warning') }}"
                          style="font-size:14px;padding:6px 16px;">
                        {{ $transaction->status_label }}
                    </span>
                </div>
                @if($transaction->failure_reason)
                    <div class="alert-m alert-m-danger mt-3" style="margin-bottom:0;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <strong>سبب الفشل:</strong> {{ $transaction->failure_reason }}
                    </div>
                @endif
                @if($transaction->card_generated_at)
                    <div style="margin-top:10px;font-size:12px;color:var(--muted);">
                        <i class="bi bi-clock"></i>
                        تاريخ التوليد: {{ $transaction->card_generated_at->format('Y-m-d H:i:s') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-key" style="color:var(--accent)"></i>
                بيانات الكرت
            </div>
            <div class="card-body">
                @if($transaction->mikrotik_username)
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="padding:6px 0;font-size:13px;color:var(--muted);width:120px;">اسم المستخدم:</td>
                            <td style="padding:6px 0;"><span class="code-m" style="font-size:14px;">{{ $transaction->mikrotik_username }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;font-size:13px;color:var(--muted);">كلمة المرور:</td>
                            <td style="padding:6px 0;"><span class="code-m" style="font-size:14px;">{{ $transaction->mikrotik_password }}</span></td>
                        </tr>
                    </table>
                @else
                    <div style="color:var(--muted);font-size:14px;">
                        <i class="bi bi-hourglass-split"></i> لم يتم توليد الكرت بعد.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-person" style="color:var(--accent)"></i>
                العميل
            </div>
            <div class="card-body">
                @if($transaction->user)
                    <div style="font-size:16px;font-weight:600;">{{ $transaction->user->full_name }}</div>
                    <div style="font-size:13px;color:var(--muted);margin-top:2px;">{{ $transaction->user->phone }}</div>
                @else
                    <div style="font-size:14px;color:var(--muted);">العميل من Webhook:</div>
                    <div style="font-size:16px;font-weight:600;margin-top:4px;">{{ $transaction->webhook_full_name ?? '—' }}</div>
                    <div style="font-size:13px;color:var(--muted);">{{ $transaction->webhook_phone ?? '—' }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-credit-card" style="color:var(--accent)"></i>
                بيانات الدفع
            </div>
            <div class="card-body">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:5px 0;font-size:13px;color:var(--muted);width:100px;">المبلغ:</td>
                        <td style="padding:5px 0;font-weight:600;">
                            {{ $transaction->webhook_amount ? number_format($transaction->webhook_amount, 2) . ' ر.ي' : '—' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;font-size:13px;color:var(--muted);">رقم المرجع:</td>
                        <td style="padding:5px 0;"><span class="code-m">{{ $transaction->jeeb_reference ?? '—' }}</span></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;font-size:13px;color:var(--muted);">الباقة:</td>
                        <td style="padding:5px 0;font-weight:500;">{{ $transaction->profile->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;font-size:13px;color:var(--muted);">المُفعّل:</td>
                        <td style="padding:5px 0;">{{ $transaction->activatedByAdmin->username ?? '—' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    @if($transaction->rawWebhook)
        <div class="col-12">
            <div class="card anim-fade-in">
                <div class="card-header">
                    <i class="bi bi-broadcast" style="color:var(--accent)"></i>
                    السجل الخام للإشعار المالي
                </div>
                <div class="card-body">
                    <pre class="code-m" style="padding:14px;background:var(--bg);border-radius:8px;max-height:200px;overflow:auto;direction:ltr;text-align:left;white-space:pre-wrap;word-break:break-all;font-size:12px;line-height:1.5;">{{ $transaction->rawWebhook->raw_payload }}</pre>
                    <div style="margin-top:8px;font-size:12px;color:var(--muted);display:flex;gap:16px;flex-wrap:wrap;">
                        <span><i class="bi bi-clock"></i> استُقبل في: {{ $transaction->rawWebhook->received_at }}</span>
                        <span><i class="bi bi-globe"></i> IP: {{ $transaction->rawWebhook->source_ip }}</span>
                        <span>
                            مُحلّل بنجاح:
                            <span style="color:{{ $transaction->rawWebhook->parsed_successfully ? 'var(--success)' : 'var(--m-red)' }};">
                                {{ $transaction->rawWebhook->parsed_successfully ? '✓' : '✗' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="mt-4 d-flex gap-2 anim-fade-in">
    <a href="{{ URL::previous() }}" class="btn-m btn-m-outline">
        <i class="bi bi-arrow-right"></i> العودة
    </a>
    @if(in_array($transaction->status, ['pending_match', 'manual_pending']) && $transaction->profile_id)
        <form action="{{ route('admin.transactions.activate', $transaction) }}" method="POST"
              onsubmit="return confirm('تأكيد التفعيل اليدوي؟')">
            @csrf
            <button class="btn-m btn-m-success">
                <i class="bi bi-play-fill"></i> تفعيل يدوي
            </button>
        </form>
    @endif
    @if($transaction->status === 'failed')
        <form action="{{ route('admin.transactions.retry', $transaction) }}" method="POST">
            @csrf
            <button class="btn-m btn-m-outline">
                <i class="bi bi-arrow-clockwise"></i> إعادة المحاولة
            </button>
        </form>
    @endif
</div>
@endsection