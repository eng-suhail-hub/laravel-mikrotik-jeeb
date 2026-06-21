@extends('admin.layouts.app')

@section('title', $challenge ? 'تعديل التحدي' : 'إضافة تحدي جديد')

@section('content')
<div class="page-header">
    <h2>
        <i class="bi {{ $challenge ? 'bi-pencil' : 'bi-plus-lg' }}" style="color:var(--accent)"></i>
        {{ $challenge ? 'تعديل التحدي' : 'إضافة تحدي جديد' }}
    </h2>
</div>

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-trophy" style="color:var(--accent)"></i>
        معلومات التحدي
    </div>
    <div class="card-body">
        <form action="{{ $challenge ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" method="POST">
            @csrf @if($challenge) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">الاسم</label>
                    <input type="text" name="name" class="form-control-m" value="{{ old('name', $challenge->name ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">فعال</label>
                    <select name="is_active" class="form-control-m">
                        <option value="1" {{ old('is_active', $challenge->is_active ?? true) ? 'selected' : '' }}>نعم</option>
                        <option value="0" {{ old('is_active', $challenge->is_active ?? true) ? '' : 'selected' }}>لا</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">أقصى عدد إنجاز</label>
                    <input type="number" name="max_completions" class="form-control-m"
                           value="{{ old('max_completions', $challenge->max_completions ?? 0) }}" min="0">
                </div>
                <div class="col-12">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">الوصف</label>
                    <textarea name="description" class="form-control-m" rows="3">{{ old('description', $challenge->description ?? '') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">تاريخ البداية</label>
                    <input type="datetime-local" name="starts_at" class="form-control-m"
                           value="{{ old('starts_at', $challenge->starts_at ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">تاريخ النهاية</label>
                    <input type="datetime-local" name="ends_at" class="form-control-m"
                           value="{{ old('ends_at', $challenge->ends_at ?? '') }}">
                </div>
            </div>

            <hr style="border-color:var(--border);margin:24px 0;">

            <h5 style="font-weight:700;font-size:15px;margin-bottom:12px;">
                <i class="bi bi-list-check" style="color:var(--accent);"></i> الشروط
            </h5>
            <div id="conditions-container">
                @if($challenge)
                    @foreach($challenge->conditions as $i => $cond)
                        <div class="row mb-2 condition-row">
                            <div class="col-md-3">
                                <input type="text" name="conditions[{{ $i }}][condition_type]" class="form-control-m" placeholder="النوع" value="{{ $cond->condition_type }}">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="conditions[{{ $i }}][operator]" class="form-control-m" placeholder="المعامل" value="{{ $cond->operator }}">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="conditions[{{ $i }}][value]" class="form-control-m" placeholder='{"min": 1}' value="{{ json_encode($cond->value) }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="conditions[{{ $i }}][logic_group]" class="form-control-m" placeholder="مجموعة" value="{{ $cond->logic_group }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn-m btn-m-danger btn-m-sm remove-row" style="width:100%;">×</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" class="btn-m btn-m-outline btn-m-sm mb-3" id="add-condition">
                <i class="bi bi-plus-lg"></i> إضافة شرط
            </button>

            <hr style="border-color:var(--border);margin:24px 0;">

            <h5 style="font-weight:700;font-size:15px;margin-bottom:12px;">
                <i class="bi bi-gift" style="color:var(--accent);"></i> المكافآت
            </h5>
            <div id="rewards-container">
                @if($challenge)
                    @foreach($challenge->rewards as $i => $reward)
                        <div class="row mb-2 reward-row">
                            <div class="col-md-4">
                                <input type="text" name="rewards[{{ $i }}][reward_type]" class="form-control-m" placeholder="النوع (points)" value="{{ $reward->reward_type }}">
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="rewards[{{ $i }}][value]" class="form-control-m" placeholder='{"points": 10}' value="{{ json_encode($reward->value) }}">
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="rewards[{{ $i }}][priority]" class="form-control-m" placeholder="الأولوية" value="{{ $reward->priority }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn-m btn-m-danger btn-m-sm remove-row" style="width:100%;">×</button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" class="btn-m btn-m-outline btn-m-sm mb-3" id="add-reward">
                <i class="bi bi-plus-lg"></i> إضافة مكافأة
            </button>

            <hr style="border-color:var(--border);margin:24px 0;">

            <div class="d-flex gap-2">
                <button type="submit" class="btn-m btn-m-primary">
                    <i class="bi bi-save"></i> {{ $challenge ? 'تحديث' : 'إنشاء' }}
                </button>
                <a href="{{ route('admin.challenges.index') }}" class="btn-m btn-m-outline">
                    <i class="bi bi-x"></i> إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let condIdx = {{ $challenge ? $challenge->conditions->count() : 0 }};
    let rewIdx = {{ $challenge ? $challenge->rewards->count() : 0 }};

    document.getElementById('add-condition')?.addEventListener('click', function() {
        const html = `<div class="row mb-2 condition-row">
            <div class="col-md-3"><input type="text" name="conditions[${condIdx}][condition_type]" class="form-control-m" placeholder="النوع"></div>
            <div class="col-md-2"><input type="text" name="conditions[${condIdx}][operator]" class="form-control-m" placeholder="المعامل" value="gte"></div>
            <div class="col-md-4"><input type="text" name="conditions[${condIdx}][value]" class="form-control-m" placeholder='{"min": 1}'></div>
            <div class="col-md-2"><input type="number" name="conditions[${condIdx}][logic_group]" class="form-control-m" placeholder="مجموعة"></div>
            <div class="col-md-1"><button type="button" class="btn-m btn-m-danger btn-m-sm remove-row" style="width:100%;">×</button></div>
        </div>`;
        document.getElementById('conditions-container').insertAdjacentHTML('beforeend', html);
        condIdx++;
    });

    document.getElementById('add-reward')?.addEventListener('click', function() {
        const html = `<div class="row mb-2 reward-row">
            <div class="col-md-4"><input type="text" name="rewards[${rewIdx}][reward_type]" class="form-control-m" placeholder="النوع (points)"></div>
            <div class="col-md-5"><input type="text" name="rewards[${rewIdx}][value]" class="form-control-m" placeholder='{"points": 10}'></div>
            <div class="col-md-2"><input type="number" name="rewards[${rewIdx}][priority]" class="form-control-m" placeholder="الأولوية"></div>
            <div class="col-md-1"><button type="button" class="btn-m btn-m-danger btn-m-sm remove-row" style="width:100%;">×</button></div>
        </div>`;
        document.getElementById('rewards-container').insertAdjacentHTML('beforeend', html);
        rewIdx++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.row').remove();
        }
    });
</script>
@endpush