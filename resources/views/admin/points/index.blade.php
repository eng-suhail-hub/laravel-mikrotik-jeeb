@extends('admin.layouts.app')

@section('title', 'إدارة النقاط')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-coin" style="color:var(--accent)"></i> إدارة النقاط</h2>
        <p>أرصدة المستخدمين وحركات النقاط</p>
    </div>
    <a href="{{ route('admin.points.transactions') }}" class="btn-m btn-m-outline">
        <i class="bi bi-arrow-repeat"></i> سجل الحركات
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card accent-blue anim-fade-in">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">إجمالي الرصيد</div>
            <div class="stat-value">{{ number_format($totals['balance'] ?? $balances->sum('balance'), 0) }}</div>
            <div class="stat-footer">{{ $balances->where('balance', '>', 0)->count() }} مستخدمين نشطين</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card accent-green anim-fade-in anim-fade-in-d2">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-label">إجمالي المكتسب</div>
            <div class="stat-value">{{ number_format($totals['earned'] ?? $balances->sum('total_earned'), 0) }}</div>
            <div class="stat-footer">منذ بداية النظام</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card accent-amber anim-fade-in anim-fade-in-d3">
            <div class="stat-glow"></div>
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">إجمالي المنصرف</div>
            <div class="stat-value">{{ number_format($totals['spent'] ?? $balances->sum('total_spent'), 0) }}</div>
            <div class="stat-footer">جميع عمليات الخصم</div>
        </div>
    </div>
</div>

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-people" style="color:var(--accent)"></i>
        أرصدة المستخدمين
    </div>
    <div class="card-body p-0">
        @if($balances->count())
            <div class="table-responsive">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الهاتف</th>
                            <th>الرصيد</th>
                            <th>الإجمالي المكتسب</th>
                            <th>الإجمالي المنصرف</th>
                            <th style="width:80px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balances as $balance)
                            <tr>
                                <td style="font-weight:600;">{{ $balance->user->full_name ?? '—' }}</td>
                                <td style="font-size:13px;">{{ $balance->user->phone ?? '—' }}</td>
                                <td style="font-weight:700;font-feature-settings:'tnum';color:var(--accent);">
                                    {{ number_format($balance->balance, 0) }}
                                </td>
                                <td style="font-feature-settings:'tnum';color:var(--success);font-weight:600;">
                                    {{ number_format($balance->total_earned, 0) }}
                                </td>
                                <td style="font-feature-settings:'tnum';color:var(--muted);font-weight:500;">
                                    {{ number_format($balance->total_spent, 0) }}
                                </td>
                                <td>
                                    <button type="button" class="btn-m btn-m-outline btn-m-sm btn-m-icon"
                                            data-bs-toggle="modal" data-bs-target="#adjustModal{{ $balance->id }}"
                                            title="تعديل الرصيد">
                                        <i class="bi bi-sliders"></i>
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade modal-m" id="adjustModal{{ $balance->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.points.adjust', $balance->user) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title" style="font-size:15px;font-weight:700;">
                                                    تعديل الرصيد — {{ $balance->user->full_name ?? 'مستخدم' }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div style="margin-bottom:16px;padding:12px;background:var(--bg);border-radius:8px;display:flex;justify-content:space-between;">
                                                    <span style="font-weight:600;">الرصيد الحالي:</span>
                                                    <span style="font-weight:700;color:var(--accent);">{{ number_format($balance->balance, 0) }}</span>
                                                </div>
                                                <div class="mb-3">
                                                    <label style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;display:block;">نوع التعديل</label>
                                                    <select name="type" class="form-control-m" required>
                                                        <option value="credit">إيداع (إضافة نقاط)</option>
                                                        <option value="debit">خصم (سحب نقاط)</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;display:block;">الكمية</label>
                                                    <input type="number" name="amount" class="form-control-m" required min="1" placeholder="أدخل عدد النقاط">
                                                </div>
                                                <div class="mb-3">
                                                    <label style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;display:block;">السبب</label>
                                                    <input type="text" name="reason" class="form-control-m" required placeholder="سبب التعديل">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn-m btn-m-outline btn-m-sm" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn-m btn-m-primary btn-m-sm">تأكيد</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-wallet"></i>
                <p>لا توجد أرصدة بعد.</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($balances, 'links'))
    <div class="mt-3">{{ $balances->links() }}</div>
@endif
@endsection