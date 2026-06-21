@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>طباعة القسائم</h1>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">اختيار القسائم للطباعة</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.vouchers.preview') }}" target="_blank">
                        @csrf
                        <div class="mb-3">
                            <label>القالب</label>
                            <select name="theme_id" class="form-select" required>
                                @foreach($themes as $theme)
                                <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>اسم المستخدم</th>
                                    <th>كلمة المرور</th>
                                    <th>الباقة</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentCards as $card)
                                <tr>
                                    <td><input type="checkbox" name="transaction_ids[]" value="{{ $card->id }}"></td>
                                    <td>{{ $card->mikrotik_username }}</td>
                                    <td>{{ $card->mikrotik_password }}</td>
                                    <td>{{ $card->profile?->name }}</td>
                                    <td>{{ $card->created_at }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5">لا توجد قسائم جاهزة للطباعة.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">معاينة وطباعة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('input[name="transaction_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
