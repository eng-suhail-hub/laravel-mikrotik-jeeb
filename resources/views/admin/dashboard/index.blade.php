@extends('admin.layouts.app')

@section('title', 'لوحة المعلومات')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-grid-1x2-fill" style="color:var(--accent)"></i> لوحة المعلومات</h2>
    <p>نظرة عامة على النظام، المعاملات، وإحصائيات الأداء</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card accent-blue anim-fade-in anim-fade-in-d1">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-router"></i></div>
            <div class="stat-label">حالة الراوتر</div>
            <div class="stat-value" style="display:flex;align-items:center;gap:10px;">
                <span class="router-status-dot {{ $stats['router_connected'] ? 'online' : 'offline' }}" style="width:12px;height:12px;"></span>
                {{ $stats['router_connected'] ? 'متصل' : 'غير متصل' }}
            </div>
            @if(!$stats['router_connected'])
                <div class="stat-footer">
                    <a href="{{ route('admin.router.index') }}" class="link-m">إعداد الراوتر ←</a>
                </div>
            @else
                <div class="stat-footer">النظام جاهز للتوليد</div>
            @endif
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-amber anim-fade-in anim-fade-in-d2">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">بانتظار المطابقة</div>
            <div class="stat-value">{{ $stats['pending_count'] }}</div>
            <div class="stat-footer">
                <a href="{{ route('admin.transactions.pending') }}" class="link-m">عرض الكل ←</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-blue anim-fade-in anim-fade-in-d3">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-gear"></i></div>
            <div class="stat-label">قيد التوليد</div>
            <div class="stat-value">{{ $stats['processing_count'] }}</div>
            <div class="stat-footer">جاري إنشاء كروت جديدة</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-green anim-fade-in anim-fade-in-d4">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-label">مكتملة اليوم</div>
            <div class="stat-value">{{ $stats['completed_today'] }}</div>
            <div class="stat-footer">{{ $stats['failed_count'] }} فشلت — <a href="{{ route('admin.transactions.index', ['status' => 'failed']) }}" class="link-m">عرض</a></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card accent-blue anim-fade-in anim-fade-in-d5">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-coin"></i></div>
            <div class="stat-label">إجمالي النقاط</div>
            <div class="stat-value">{{ number_format($stats['total_points_balance'], 0) }}</div>
            <div class="stat-footer">{{ $stats['total_users_with_points'] }} مستخدمين لديهم رصيد</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-blue anim-fade-in anim-fade-in-d5">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">سعر النقطة</div>
            <div class="stat-value">{{ number_format($stats['point_price'], 0) }}</div>
            <div class="stat-footer">ريال يمني</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-amber anim-fade-in anim-fade-in-d6">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-trophy"></i></div>
            <div class="stat-label">تحديات نشطة</div>
            <div class="stat-value">{{ $stats['active_challenges'] }}</div>
            <div class="stat-footer">
                <a href="{{ route('admin.challenges.index') }}" class="link-m">إدارة التحديات ←</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card accent-green anim-fade-in anim-fade-in-d6">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-activity"></i></div>
            <div class="stat-label">عمليات V2 اليوم</div>
            <div class="stat-value">{{ $stats['v2_transactions_today'] }}</div>
            <div class="stat-footer">{{ $stats['webhooks_today'] }} إشعار مالي اليوم</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-clock-history" style="color:var(--accent)"></i>
                آخر العمليات
                <a href="{{ route('admin.transactions.index') }}" class="link-m" style="margin-right:auto;font-size:12px;">عرض الكل ←</a>
            </div>
            <div class="card-body p-0">
                @if($recentTransactions->count())
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الحالة</th>
                                <th>الباقة</th>
                                <th>المبلغ</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $tx)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.transactions.show', $tx) }}" class="link-m" style="font-weight:600;">
                                            #{{ $tx->id }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge-status bg-{{ $tx->status === 'completed' ? 'success' : ($tx->status === 'failed' ? 'danger' : ($tx->status === 'processing' ? 'info' : 'warning')) }}">
                                            {{ $tx->status_label }}
                                        </span>
                                    </td>
                                    <td style="font-weight:500;">{{ $tx->profile->name ?? '—' }}</td>
                                    <td style="font-feature-settings:'tnum';font-weight:600;">
                                        {{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.transactions.show', $tx) }}" class="btn-m btn-m-outline btn-m-sm btn-m-icon">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>لا توجد عمليات بعد.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-broadcast" style="color:var(--accent)"></i>
                آخر الإشعارات المالية
                <span style="margin-right:auto;font-size:11px;color:var(--muted);font-weight:400;">الإجمالي: {{ $stats['webhooks_total'] }}</span>
            </div>
            <div class="card-body p-0">
                @if($recentWebhooks->count())
                    <div style="padding:0;">
                        @foreach($recentWebhooks as $wh)
                            <div style="padding:14px 20px;border-bottom:1px solid var(--border);transition:background 0.15s;"
                                 onmouseover="this.style.background='rgba(0,102,177,0.02)'"
                                 onmouseout="this.style.background='transparent'">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                    <span style="font-size:12px;color:var(--muted);">
                                        <i class="bi bi-clock" style="font-size:11px;"></i> {{ $wh->received_at }}
                                    </span>
                                    <span class="badge-status bg-{{ $wh->parsed_successfully ? 'success' : 'danger' }}" style="font-size:10px;">
                                        {{ $wh->parsed_successfully ? 'مُحلّل' : 'خطأ' }}
                                    </span>
                                </div>
                                <div style="font-size:12.5px;color:var(--fg);font-family:'SF Mono',ui-monospace,Menlo,monospace;direction:ltr;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;opacity:0.7;">
                                    {{ mb_substr($wh->raw_payload, 0, 100) }}...
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-wifi-off"></i>
                        <p>لا توجد إشعارات مالية بعد.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection