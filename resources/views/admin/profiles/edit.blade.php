@extends('admin.layouts.app')
@section('title', 'تعديل باقة')

@section('content')
<h2 class="mb-4"><i class="bi bi-pencil"></i> تعديل الباقة: {{ $profile->name }}</h2>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.profiles.update', $profile) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">اسم الباقة</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $profile->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">اسم الباقة في الراوتر (غير قابل للتعديل)</label>
                    <input type="text" name="mikrotik_profile_name" class="form-control" readonly
                           value="{{ old('mikrotik_profile_name', $profile->mikrotik_profile_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">السعر</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" required
                           value="{{ old('price', $profile->price) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">المدة (بالساعات)</label>
                    <input type="number" min="1" name="duration_hours" class="form-control" required
                           value="{{ old('duration_hours', $profile->duration_hours) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">حد السرعة</label>
                    <input type="text" name="speed_limit" class="form-control" value="{{ old('speed_limit', $profile->speed_limit) }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ $profile->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">باقة نشطة</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> حفظ التعديلات</button>
                <a href="{{ route('admin.profiles.index') }}" class="btn btn-outline-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
