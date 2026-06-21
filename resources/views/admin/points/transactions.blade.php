@extends('admin.layouts.app')

@section('title', 'حركات النقاط')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-arrow-repeat" style="color:var(--accent)"></i> حركات النقاط</h2>
    <p>سجل جميع حركات الإيداع والخصم</p>
</div>

<form method="GET" class="mb-3">
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="text" name="search" class="form-control-m" placeholder="بحث باسم المستخدم أو الهاتف"
               value="{{ request('search') }}" style="max-width:260px;">
        <select name="type" class="form-control-m" style="width:auto;min-width:120px;">
            <option value="">الكل</option>
            <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>إيداع</option>
            <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>خصم</option>
        </select>
        <button type="submit" class="btn-m btn-m-primary">
            <i class="bi bi-search"></i> بحث
        </button>
    </div>
</form>

<div class="card anim-fade-in">
    <div class="card-body p-0">
        @if($transactions->count())
            <div class="table-responsive">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>الرصيد قبل</th>
                            <th>الرصيد بعد</th>
                            <th>السبب</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td>
                                    <div style="font-weight:500;">{{ $tx->user->full_name ?? '—' }}</div>
                                    <div style="font-size:12px;color:var(--muted);">{{ $tx->user->phone ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="badge-status bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                        {{ $tx->type === 'credit' ? 'إيداع' : 'خصم' }}
                                    </span>
                                </td>
                                <td style="font-weight:700;font-feature-settings:'tnum';">
                                    {{ number_format($tx->amount, 0) }}
                                </td>
                                <td style="font-feature-settings:'tnum';color:var(--muted);">{{ number_format($tx->balance_before, 0) }}</td>
                                <td style="font-feature-settings:'tnum';font-weight:600;">{{ number_format($tx->balance_after, 0) }}</td>
                                <td style="font-size:13px;">{{ $tx->reason }}</td>
                                <td style="font-size:12px;color:var(--muted);">{{ $tx->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-arrow-repeat"></i>
                <p>لا توجد حركات.</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($transactions, 'links'))
    <div class="mt-3">{{ $transactions->links() }}</div>
@endif
@endsection