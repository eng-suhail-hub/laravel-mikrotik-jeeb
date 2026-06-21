@extends('admin.layouts.app')

@section('title', 'صيانة الراوتر')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-tools" style="color:var(--accent)"></i> صيانة الراوتر</h2>
    <p>أدوات الصيانة والدعم الفني لمخدم MikroTik</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-gear-wide-connected" style="color:var(--accent)"></i>
                إجراءات الصيانة
            </div>
            <div class="card-body">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'backup_db') }}">
                        @csrf
                        <button type="submit" class="btn-m btn-m-outline" style="width:100%;justify-content:center;border-color:rgba(0,102,177,0.2);color:var(--accent);">
                            <i class="bi bi-download"></i> نسخ احتياطي لقاعدة بيانات User Manager
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'clear_logs') }}">
                        @csrf
                        <button type="submit" class="btn-m btn-m-outline" style="width:100%;justify-content:center;border-color:rgba(245,158,11,0.2);color:#b45309;">
                            <i class="bi bi-trash"></i> مسح سجلات User Manager
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.execute', 'rebuild_db') }}">
                        @csrf
                        <button type="submit" class="btn-m btn-m-outline" style="width:100%;justify-content:center;border-color:rgba(228,0,43,0.2);color:var(--m-red);">
                            <i class="bi bi-arrow-repeat"></i> إعادة بناء قاعدة بيانات User Manager
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-clock-history" style="color:var(--accent)"></i>
                سجل الصيانة
            </div>
            <div class="card-body p-0">
                @if($logs->count())
                    <div class="table-responsive">
                        <table class="table-clean">
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
                                        <td style="font-size:13px;">{{ $log->created_at }}</td>
                                        <td style="font-weight:500;">{{ $log->action }}</td>
                                        <td style="font-size:13px;">{{ $log->admin?->name ?? '—' }}</td>
                                        <td>
                                            @if($log->status === 'success')
                                                <span class="badge-status bg-success">نجاح</span>
                                            @else
                                                <span class="badge-status bg-danger">فشل</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-tools"></i>
                        <p>لا توجد سجلات صيانة بعد.</p>
                    </div>
                @endif
            </div>
        </div>

        @if(method_exists($logs, 'links'))
            <div class="mt-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection