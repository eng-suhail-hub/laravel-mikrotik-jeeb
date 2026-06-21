@extends('admin.layouts.app')

@section('title', 'العمليات المعلقة')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-clock-history" style="color:var(--accent)"></i> العمليات المعلقة</h2>
    <p>عمليات تنتظر المطابقة مع إشعار الدفع، أو تنتظر التفعيل اليدوي من الأدمن</p>
</div>

<form method="GET" class="mb-3">
    <div style="display:flex;gap:8px;">
        <input type="text" name="search" class="form-control-m" placeholder="ابحث بالاسم، الهاتف، أو رقم المرجع..."
               value="{{ request('search') }}" style="max-width:360px;">
        <button class="btn-m btn-m-outline">
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
                            <th>#</th>
                            <th>الحالة</th>
                            <th>العميل</th>
                            <th>الباقة</th>
                            <th>المبلغ</th>
                            <th>المرجع</th>
                            <th>الوقت</th>
                            <th style="width:130px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr style="{{ $tx->status === 'manual_pending' ? 'background:rgba(245,158,11,0.04);' : '' }}">
                                <td style="font-weight:600;color:var(--muted);">{{ $tx->id }}</td>
                                <td>
                                    <span class="badge-status bg-{{ $tx->status === 'pending_match' ? 'secondary' : 'warning' }}">
                                        {{ $tx->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:500;">{{ $tx->webhook_full_name ?? $tx->user->full_name ?? '—' }}</div>
                                    <div style="font-size:12px;color:var(--muted);">{{ $tx->webhook_phone ?? $tx->user->phone ?? '—' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:500;">{{ $tx->profile->name ?? 'غير محددة' }}</div>
                                    @if(!$tx->profile_id || $tx->status === 'manual_pending')
                                        <button class="link-m" style="background:none;border:none;cursor:pointer;font-size:12px;padding:0;"
                                                data-bs-toggle="modal" data-bs-target="#assignModal{{ $tx->id }}">
                                            تعيين باقة
                                        </button>
                                    @endif
                                </td>
                                <td style="font-feature-settings:'tnum';font-weight:600;">
                                    {{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}
                                </td>
                                <td><span class="code-m">{{ $tx->jeeb_reference ?? '—' }}</span></td>
                                <td style="font-size:12px;color:var(--muted);">{{ $tx->created_at->diffForHumans() }}</td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:nowrap;">
                                        <a href="{{ route('admin.transactions.show', $tx) }}" class="btn-m btn-m-outline btn-m-sm btn-m-icon" title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.transactions.activate', $tx) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('تأكيد التفعيل اليدوي؟ سيتم توليد الكرت الآن.')">
                                            @csrf
                                            <button class="btn-m btn-m-success btn-m-sm" {{ !$tx->profile_id ? 'disabled' : '' }}
                                                    title="تفعيل" style="{{ !$tx->profile_id ? 'opacity:0.4;cursor:not-allowed;' : '' }}">
                                                <i class="bi bi-play-fill"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="modal fade modal-m" id="assignModal{{ $tx->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('admin.transactions.assignProfile', $tx) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" style="font-size:15px;font-weight:700;">
                                                            تعيين باقة للعملية #{{ $tx->id }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <label style="font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;display:block;">اختر الباقة:</label>
                                                        <select name="profile_id" class="form-control-m" required>
                                                            @foreach($profiles as $p)
                                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ number_format($p->price, 0) }} ر.ي)</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn-m btn-m-outline btn-m-sm" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn-m btn-m-primary btn-m-sm">حفظ</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-check2-circle" style="font-size:48px;color:var(--success);opacity:0.5;"></i>
                <p>لا توجد عمليات معلقة. 🎉</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($transactions, 'links'))
    <div class="mt-3">{{ $transactions->links() }}</div>
@endif
@endsection