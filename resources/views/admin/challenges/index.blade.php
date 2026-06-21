@extends('admin.layouts.app')

@section('title', 'التحديات')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-trophy" style="color:var(--accent)"></i> التحديات</h2>
        <p>إدارة تحديات المكافآت والنقاط للمستخدمين</p>
    </div>
    <a href="{{ route('admin.challenges.create') }}" class="btn-m btn-m-primary">
        <i class="bi bi-plus-lg"></i> إضافة تحدي
    </a>
</div>

<div class="card anim-fade-in">
    <div class="card-body p-0">
        @if($challenges->count())
            <div class="table-responsive">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الحالة</th>
                            <th>الشروط</th>
                            <th>المكافآت</th>
                            <th>البداية</th>
                            <th>النهاية</th>
                            <th style="width:100px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($challenges as $challenge)
                            <tr>
                                <td style="font-weight:600;">{{ $challenge->name }}</td>
                                <td>
                                    @if($challenge->is_active)
                                        <span class="badge-status bg-success">نشط</span>
                                    @else
                                        <span class="badge-status bg-secondary">معطل</span>
                                    @endif
                                </td>
                                <td style="font-feature-settings:'tnum';">{{ $challenge->conditions_count }}</td>
                                <td style="font-feature-settings:'tnum';">{{ $challenge->rewards_count }}</td>
                                <td style="font-size:13px;">{{ $challenge->starts_at ? $challenge->starts_at->format('Y-m-d') : '—' }}</td>
                                <td style="font-size:13px;">{{ $challenge->ends_at ? $challenge->ends_at->format('Y-m-d') : '—' }}</td>
                                <td>
                                    <div style="display:flex;gap:4px;">
                                        <a href="{{ route('admin.challenges.edit', $challenge) }}" class="btn-m btn-m-outline btn-m-sm btn-m-icon" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.challenges.destroy', $challenge) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا التحدي؟')">
                                            @csrf @method('DELETE')
                                            <button class="btn-m btn-m-danger btn-m-sm btn-m-icon" title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <i class="bi bi-trophy" style="opacity:0.2;"></i>
                <p>لا توجد تحديات. قم بإضافة تحدي جديد.</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($challenges, 'links'))
    <div class="mt-3">{{ $challenges->links() }}</div>
@endif
@endsection