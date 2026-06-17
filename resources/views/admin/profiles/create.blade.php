@extends('admin.layouts.app')
@section('title', 'إنشاء باقة')

@section('content')
<h2 class="mb-4"><i class="bi bi-plus-lg"></i> إنشاء باقة جديدة</h2>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.profiles.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الباقة <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="باقة 5000 - يوم كامل" value="{{ old('name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم الباقة في الراوتر (User Manager) <span class="text-danger">*</span></label>
                    <input type="text" name="mikrotik_profile_name" class="form-control" required
                           placeholder="um-5k-daily" value="{{ old('mikrotik_profile_name') }}">
                    <small class="text-muted">يجب أن يطابق اسم Profile موجود في User Manager</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">السعر <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" required
                           value="{{ old('price') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">المدة (بالساعات) <span class="text-danger">*</span></label>
                    <input type="number" min="1" name="duration_hours" class="form-control" required
                           value="{{ old('duration_hours', 24) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">حد السرعة (اختياري)</label>
                    <input type="text" name="speed_limit" class="form-control"
                           placeholder="10M/5M" value="{{ old('speed_limit') }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                        <label class="form-check-label" for="is_active">باقة نشطة (تظهر في تطبيق Flutter)</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ</button>
                <a href="{{ route('admin.profiles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
