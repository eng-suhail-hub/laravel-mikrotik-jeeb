@extends('admin.layouts.app')
@section('title', 'الباقات')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-tags"></i> إدارة الباقات</h2>
    <form action="{{ route('admin.profiles.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-arrow-repeat"></i> مزامنة الباقات من المايكروتك
        </button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>اسم الباقة</th>
                        <th>اسمها في المايكروتك</th>
                        <th>السعر</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td>{{ $profile->id }}</td>
                            <td><strong>{{ $profile->name }}</strong></td>
                            <td><code>{{ $profile->mikrotik_profile_name }}</code></td>
                            <td>{{ number_format($profile->price, 2) }}</td>
                            <td><span class="badge bg-info text-dark">{{ $profile->formatted_validity }}</span></td>
                            <td>
                                @if($profile->is_active)
                                    <span class="badge bg-success">نشطة</span>
                                @else
                                    <span class="badge bg-secondary">معطلة</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.profiles.edit', $profile) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.profiles.destroy', $profile) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">لا توجد باقات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $profiles->links() }}</div>
@endsection
