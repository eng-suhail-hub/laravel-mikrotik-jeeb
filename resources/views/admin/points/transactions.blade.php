@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>حركات النقاط</h1>

    <form method="GET" class="row mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="بحث باسم المستخدم أو الهاتف" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">الكل</option>
                <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>إيداع</option>
                <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>خصم</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">بحث</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <table class="table">
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
                    @forelse($transactions as $tx)
                    <tr>
                        <td>{{ $tx->user->full_name ?? '—' }}<br><small>{{ $tx->user->phone ?? '' }}</small></td>
                        <td><span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">{{ $tx->type === 'credit' ? 'إيداع' : 'خصم' }}</span></td>
                        <td>{{ number_format($tx->amount, 2) }}</td>
                        <td>{{ number_format($tx->balance_before, 2) }}</td>
                        <td>{{ number_format($tx->balance_after, 2) }}</td>
                        <td>{{ $tx->reason }}</td>
                        <td>{{ $tx->created_at }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">لا توجد حركات</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection
