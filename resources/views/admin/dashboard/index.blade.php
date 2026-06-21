@extends('admin.layouts.app')

@section('title', 'لوحة المعلومات')

@section('content')
<h2 class="mb-4">لوحة المعلومات</h2>

{{-- بطاقات الإحصائيات --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-{{ $stats['router_connected'] ? 'success' : 'danger' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">حالة الراوتر</h6>
                        <h4 class="mb-0">{{ $stats['router_connected'] ? 'متصل' : 'غير متصل' }}</h4>
                    </div>
                    <i class="bi bi-router fs-1 text-{{ $stats['router_connected'] ? 'success' : 'danger' }}"></i>
                </div>
                @if(!$stats['router_connected'])
                    <a href="{{ route('admin.router.index') }}" class="btn btn-sm btn-outline-danger mt-2">
                        إعداد الراوتر <i class="bi bi-arrow-left"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <h6 class="text-muted mb-1">عمليات معلقة</h6>
                <h4 class="mb-0">{{ $stats['pending_count'] }}</h4>
                <a href="{{ route('admin.transactions.pending') }}" class="small">عرض الكل</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="text-muted mb-1">قيد التوليد</h6>
                <h4 class="mb-0">{{ $stats['processing_count'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <h6 class="text-muted mb-1">مكتملة اليوم</h6>
                <h4 class="mb-0">{{ $stats['completed_today'] }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- V2: بطاقات إضافية --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <h6 class="text-muted mb-1">إجمالي النقاط</h6>
                <h4 class="mb-0">{{ number_format($stats['total_points_balance'], 0) }}</h4>
                <small class="text-muted">{{ $stats['total_users_with_points'] }} مستخدمين</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <h6 class="text-muted mb-1">سعر النقطة</h6>
                <h4 class="mb-0">{{ number_format($stats['point_price'], 0) }}</h4>
                <small class="text-muted">ريال يمني</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <h6 class="text-muted mb-1">تحديات نشطة</h6>
                <h4 class="mb-0">{{ $stats['active_challenges'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <h6 class="text-muted mb-1">عمليات V2 اليوم</h6>
                <h4 class="mb-0">{{ $stats['v2_transactions_today'] }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- آخر العمليات --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> آخر العمليات</h5>
            </div>
            <div class="card-body p-0">
                @if($recentTransactions->count())
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th><th>الحالة</th><th>الباقة</th><th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $tx)
                                    <tr>
                                        <td><a href="{{ route('admin.transactions.show', $tx) }}">{{ $tx->id }}</a></td>
                                        <td>
                                            <span class="badge status-badge bg-{{ $tx->status === 'completed' ? 'success' : ($tx->status === 'failed' ? 'danger' : 'warning text-dark') }}">
                                                {{ $tx->status_label }}
                                            </span>
                                        </td>
                                        <td>{{ $tx->profile->name ?? '—' }}</td>
                                        <td>{{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-3 text-muted">لا توجد عمليات بعد.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- آخر Webhooks --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-broadcast"></i> آخر الإشعارات المالية</h5>
            </div>
            <div class="card-body p-0">
                @if($recentWebhooks->count())
                    <div class="list-group list-group-flush">
                        @foreach($recentWebhooks as $wh)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">{{ $wh->received_at }}</small>
                                    <span class="badge bg-{{ $wh->parsed_successfully ? 'success' : 'danger' }}">
                                        {{ $wh->parsed_successfully ? '✓ مُحلّل' : '✗ خطأ' }}
                                    </span>
                                </div>
                                <div class="small mt-1">{{ mb_substr($wh->raw_payload, 0, 100) }}...</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-muted">لا توجد إشعارات بعد.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
