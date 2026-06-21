@extends('admin.layouts.app')

@section('title', 'طباعة القسائم')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-printer" style="color:var(--accent)"></i> طباعة القسائم</h2>
    <p>اختيار وطباعة قسائم الإنترنت للمستخدمين</p>
</div>

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-printer" style="color:var(--accent)"></i>
        اختيار القسائم للطباعة
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.vouchers.preview') }}" target="_blank">
            @csrf
            <div class="mb-3">
                <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">القالب</label>
                <select name="theme_id" class="form-control-m" style="max-width:300px;" required>
                    @foreach($themes as $theme)
                        <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($recentCards->count())
                <div class="table-responsive">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th style="width:40px;">
                                    <input type="checkbox" id="selectAll"
                                           style="width:16px;height:16px;border-radius:4px;border:2px solid var(--border);cursor:pointer;">
                                </th>
                                <th>اسم المستخدم</th>
                                <th>كلمة المرور</th>
                                <th>الباقة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCards as $card)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="transaction_ids[]" value="{{ $card->id }}"
                                               style="width:16px;height:16px;border-radius:4px;border:2px solid var(--border);cursor:pointer;">
                                    </td>
                                    <td><span class="code-m">{{ $card->mikrotik_username }}</span></td>
                                    <td><span class="code-m">{{ $card->mikrotik_password }}</span></td>
                                    <td style="font-weight:500;">{{ $card->profile?->name }}</td>
                                    <td style="font-size:13px;color:var(--muted);">{{ $card->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn-m btn-m-primary">
                        <i class="bi bi-eye"></i> معاينة وطباعة
                    </button>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-printer"></i>
                    <p>لا توجد قسائم جاهزة للطباعة.</p>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="transaction_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush