@extends('admin.layouts.app')
@section('title', 'كل العمليات')

@section('content')
<h2 class="mb-4"><i class="bi bi-list-check"></i> كل العمليات</h2>

{{-- فلترة بالحالة --}}
<form method="GET" class="mb-3 d-flex gap-2">
    <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <option value="pending_match" {{ request('status') === 'pending_match' ? 'selected' : '' }}>بانتظار المطابقة</option>
        <option value="matched" {{ request('status') === 'matched' ? 'selected' : '' }}>تمت المطابقة</option>
        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>قيد التوليد</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتملة</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فشلت</option>
        <option value="manual_pending" {{ request('status') === 'manual_pending' ? 'selected' : '' }}>بانتظار التفعيل اليدوي</option>
    </select>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>الحالة</th><th>العميل</th><th>الباقة</th>
                        <th>المبلغ</th><th>المُفعّل</th><th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td><a href="{{ route('admin.transactions.show', $tx) }}">{{ $tx->id }}</a></td>
                            <td>
                                <span class="badge status-badge bg-{{ $tx->status === 'completed' ? 'success' : ($tx->status === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ $tx->status_label }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $tx->webhook_full_name ?? $tx->user->full_name ?? '—' }}</div>
                                <small class="text-muted">{{ $tx->webhook_phone ?? $tx->user->phone ?? '' }}</small>
                            </td>
                            <td>{{ $tx->profile->name ?? '—' }}</td>
                            <td>{{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}</td>
                            <td>{{ $tx->activatedByAdmin->username ?? '—' }}</td>
                            <td><small>{{ $tx->created_at->format('Y-m-d H:i') }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">لا توجد عمليات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
