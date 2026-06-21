@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>صيانة الراوتر</h1>

    <div class="row mt-4">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">إجراءات الصيانة</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'backup_db') }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">نسخ احتياطي لقاعدة بيانات User Manager</button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'clear_logs') }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-info w-100">مسح سجلات User Manager</button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'rebuild_db') }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100">إعادة بناء قاعدة بيانات User Manager</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">سجل الصيانة</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>الإجراء</th>
                                <th>المسؤول</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->created_at }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->admin?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                                        {{ $log->status === 'success' ? 'نجاح' : 'فشل' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
