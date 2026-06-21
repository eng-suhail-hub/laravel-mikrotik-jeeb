@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>التحديات</h1>
        <a href="{{ route('admin.challenges.create') }}" class="btn btn-primary">إضافة تحدي جديد</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>فعال</th>
                        <th>عدد الشروط</th>
                        <th>عدد المكافآت</th>
                        <th>البداية</th>
                        <th>النهاية</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($challenges as $challenge)
                    <tr>
                        <td>{{ $challenge->name }}</td>
                        <td><span class="badge bg-{{ $challenge->is_active ? 'success' : 'secondary' }}">{{ $challenge->is_active ? 'نعم' : 'لا' }}</span></td>
                        <td>{{ $challenge->conditions_count }}</td>
                        <td>{{ $challenge->rewards_count }}</td>
                        <td>{{ $challenge->starts_at ? $challenge->starts_at->format('Y-m-d') : '—' }}</td>
                        <td>{{ $challenge->ends_at ? $challenge->ends_at->format('Y-m-d') : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.challenges.edit', $challenge) }}" class="btn btn-sm btn-warning">تعديل</a>
                            <form action="{{ route('admin.challenges.destroy', $challenge) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">لا توجد تحديات</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $challenges->links() }}
        </div>
    </div>
</div>
@endsection
