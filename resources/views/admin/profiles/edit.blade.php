@extends('admin.layouts.app')

@section('title', 'تعديل الباقة: ' . $profile->name)

@section('content')
<div class="page-header">
    <h2><i class="bi bi-pencil" style="color:var(--accent)"></i> تعديل الباقة</h2>
    <p>{{ $profile->name }}</p>
</div>

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-tag" style="color:var(--accent)"></i>
        معلومات الباقة
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.profiles.update', $profile) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">اسم الباقة</label>
                    <input type="text" name="name" class="form-control-m" required value="{{ old('name', $profile->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">اسم الباقة في الراوتر</label>
                    <input type="text" class="form-control-m" readonly value="{{ old('mikrotik_profile_name', $profile->mikrotik_profile_name) }}"
                           style="background:var(--bg);opacity:0.7;">
                    <div style="font-size:11px;color:var(--muted);margin-top:2px;">غير قابل للتعديل — تتم المزامنة من المايكروتك</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">السعر</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control-m" required
                           value="{{ old('price', $profile->price) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">المدة (بالساعات)</label>
                    <input type="number" min="1" name="duration_hours" class="form-control-m" required
                           value="{{ old('duration_hours', $profile->duration_hours) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;">حد السرعة</label>
                    <input type="text" name="speed_limit" class="form-control-m"
                           value="{{ old('speed_limit', $profile->speed_limit) }}" placeholder="مثال: 10M">
                </div>
                <div class="col-12">
                    <label class="form-check-label" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                               {{ $profile->is_active ? 'checked' : '' }}
                               style="width:18px;height:18px;border-radius:4px;border:2px solid var(--border);cursor:pointer;">
                        <span style="font-size:14px;font-weight:500;">باقة نشطة</span>
                    </label>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn-m btn-m-primary">
                    <i class="bi bi-save"></i> حفظ التعديلات
                </button>
                <a href="{{ route('admin.profiles.index') }}" class="btn-m btn-m-outline">
                    <i class="bi bi-x"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection