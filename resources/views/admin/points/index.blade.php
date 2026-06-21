@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>إدارة النقاط</h1>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">أرصدة المستخدمين</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>الهاتف</th>
                                <th>الرصيد</th>
                                <th>الإجمالي المكتسب</th>
                                <th>الإجمالي المنصرف</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($balances as $balance)
                            <tr>
                                <td>{{ $balance->user->full_name ?? '—' }}</td>
                                <td>{{ $balance->user->phone ?? '—' }}</td>
                                <td>{{ number_format($balance->balance, 2) }}</td>
                                <td>{{ number_format($balance->total_earned, 2) }}</td>
                                <td>{{ number_format($balance->total_spent, 2) }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $balance->id }}">
                                        تعديل
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $balances->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">آخر الحركات</div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        @foreach($recentTransactions as $tx)
                        <li class="mb-2">
                            <small class="text-muted">{{ $tx->created_at->diffForHumans() }}</small><br>
                            <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                {{ $tx->type === 'credit' ? 'إيداع' : 'خصم' }}
                            </span>
                            {{ number_format($tx->amount, 2) }} — {{ $tx->reason }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
