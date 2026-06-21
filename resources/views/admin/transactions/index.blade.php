@extends('admin.layouts.app')

@section('title', 'كل العمليات')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-list-check" style="color:var(--accent)"></i> كل العمليات</h2>
        <p>جميع معاملات الدفع وتوليد الكروت</p>
    </div>
</div>

<form method="GET" class="mb-3">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <select name="status" class="form-control-m" style="width:auto;min-width:180px;" onchange="this.form.submit()">
            <option value="">كل الحالات</option>
            <option value="pending_match" {{ request('status') === 'pending_match' ? 'selected' : '' }}>بانتظار المطابقة</option>
            <option value="matched" {{ request('status') === 'matched' ? 'selected' : '' }}>تمت المطابقة</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>قيد التوليد</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتملة</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فشلت</option>
            <option value="manual_pending" {{ request('status') === 'manual_pending' ? 'selected' : '' }}>بانتظار التفعيل اليدوي</option>
        </select>
    </div>
</form>

<div class="card anim-fade-in">
    <div class="card-body p-0">
        @if($transactions->count())
            <div class="table-responsive">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الحالة</th>
                            <th>العميل</th>
                            <th>الباقة</th>
                            <th>المبلغ</th>
                            <th>المُفعّل</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.transactions.show', $tx) }}" class="link-m" style="font-weight:600;">
                                        #{{ $tx->id }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-status bg-{{ $tx->status === 'completed' ? 'success' : ($tx->status === 'failed' ? 'danger' : ($tx->status === 'processing' ? 'info' : ($tx->status === 'manual_pending' ? 'warning' : 'secondary'))) }}">
                                        {{ $tx->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:500;">{{ $tx->webhook_full_name ?? $tx->user->full_name ?? '—' }}</div>
                                    <div style="font-size:12px;color:var(--muted);">{{ $tx->webhook_phone ?? $tx->user->phone ?? '' }}</div>
                                </td>
                                <td>{{ $tx->profile->name ?? '—' }}</td>
                                <td style="font-feature-settings:'tnum';font-weight:600;">{{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}</td>
                                <td style="font-size:13px;">{{ $tx->activatedByAdmin->username ?? '—' }}</td>
                                <td style="font-size:12px;color:var(--muted);">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>لا توجد عمليات.</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($transactions, 'links'))
    <div class="mt-3">{{ $transactions->links() }}</div>
@endif
@endsection