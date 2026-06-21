@extends('admin.layouts.app')

@section('title', 'الباقات')

@section('content')
<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-tags" style="color:var(--accent)"></i> إدارة الباقات</h2>
        <p>الباقات المتاحة للبيع والمزامنة مع المايكروتك</p>
    </div>
    <form action="{{ route('admin.profiles.sync') }}" method="POST">
        @csrf
        <button type="submit" class="btn-m btn-m-primary">
            <i class="bi bi-arrow-repeat"></i> مزامنة من المايكروتك
        </button>
    </form>
</div>

<div class="card anim-fade-in">
    <div class="card-body p-0">
        @if($profiles->count())
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم الباقة</th>
                        <th>الاسم في المايكروتك</th>
                        <th>السعر</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                        <th style="width:90px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($profiles as $profile)
                        <tr>
                            <td style="font-weight:600;color:var(--muted);">{{ $profile->id }}</td>
                            <td><strong>{{ $profile->name }}</strong></td>
                            <td><span class="code-m">{{ $profile->mikrotik_profile_name }}</span></td>
                            <td style="font-weight:600;font-feature-settings:'tnum';">{{ number_format($profile->price, 2) }}</td>
                            <td><span style="background:rgba(0,102,177,0.08);color:var(--accent);padding:3px 10px;border-radius:5px;font-size:12px;font-weight:600;">{{ $profile->formatted_validity }}</span></td>
                            <td>
                                @if($profile->is_active)
                                    <span class="badge-status bg-success">نشطة</span>
                                @else
                                    <span class="badge-status bg-secondary">معطلة</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;">
                                    <a href="{{ route('admin.profiles.edit', $profile) }}" class="btn-m btn-m-outline btn-m-sm btn-m-icon" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.profiles.destroy', $profile) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('هل أنت متأكد من حذف هذه الباقة؟')">
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
        @else
            <div class="empty-state">
                <i class="bi bi-tag"></i>
                <p>لا توجد باقات بعد. قم بمزامنة الباقات من المايكروتك.</p>
            </div>
        @endif
    </div>
</div>

@if(method_exists($profiles, 'links'))
    <div class="mt-3">{{ $profiles->links() }}</div>
@endif
@endsection