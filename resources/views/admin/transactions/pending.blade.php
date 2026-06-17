@extends('admin.layouts.app')
@section('title', 'العمليات المعلقة')

@section('content')
<h2 class="mb-4"><i class="bi bi-clock-history"></i> العمليات المعلقة</h2>

<p class="text-muted">عمليات تنتظر المطابقة مع إشعار الدفع، أو تنتظر التفعيل اليدوي من الأدمن.</p>

{{-- نموذج البحث --}}
<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="ابحث بالاسم، الهاتف، أو رقم المرجع..."
               value="{{ request('search') }}">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>الحالة</th>
                        <th>العميل</th>
                        <th>الباقة</th>
                        <th>المبلغ</th>
                        <th>المرجع</th>
                        <th>الوقت</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr class="{{ $tx->status === 'manual_pending' ? 'table-warning' : '' }}">
                            <td>{{ $tx->id }}</td>
                            <td>
                                <span class="badge status-badge bg-{{ $tx->status === 'pending_match' ? 'secondary' : 'warning text-dark' }}">
                                    {{ $tx->status_label }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $tx->webhook_full_name ?? $tx->user->full_name ?? '—' }}</div>
                                <small class="text-muted">{{ $tx->webhook_phone ?? $tx->user->phone ?? '—' }}</small>
                            </td>
                            <td>
                                <span class="d-block">{{ $tx->profile->name ?? 'غير محددة' }}</span>
                                @if(!$tx->profile_id || $tx->status === 'manual_pending')
                                    <button class="btn btn-link btn-sm p-0" data-bs-toggle="modal"
                                            data-bs-target="#assignModal{{ $tx->id }}">
                                        تعيين باقة
                                    </button>
                                @endif
                            </td>
                            <td>{{ $tx->webhook_amount ? number_format($tx->webhook_amount, 0) : '—' }}</td>
                            <td><code>{{ $tx->jeeb_reference ?? '—' }}</code></td>
                            <td>{{ $tx->created_at->diffForHumans() }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.transactions.show', $tx) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.transactions.activate', $tx) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('تأكيد التفعيل اليدوي؟ سيتم توليد الكرت الآن.')">
                                        @csrf
                                        <button class="btn btn-success" {{ !$tx->profile_id ? 'disabled' : '' }}>
                                            <i class="bi bi-play-fill"></i> تفعيل
                                        </button>
                                    </form>
                                </div>

                                {{-- Modal تعيين الباقة --}}
                                <div class="modal fade" id="assignModal{{ $tx->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.transactions.assignProfile', $tx) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تعيين باقة للعملية #{{ $tx->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label">اختر الباقة:</label>
                                                    <select name="profile_id" class="form-select" required>
                                                        @foreach($profiles as $p)
                                                            <option value="{{ $p->id }}">{{ $p->name }} ({{ number_format($p->price, 0) }} ر.ي)</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-primary">حفظ</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">لا توجد عمليات معلقة. 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $transactions->links() }}</div>
@endsection
