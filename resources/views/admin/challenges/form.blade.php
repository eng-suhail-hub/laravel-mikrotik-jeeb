@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>{{ $challenge ? 'تعديل التحدي' : 'إضافة تحدي جديد' }}</h1>

    <form action="{{ $challenge ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" method="POST">
        @csrf @if($challenge) @method('PUT') @endif

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">الاسم</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $challenge->name ?? '') }}" required>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">فعال</label>
                <select name="is_active" class="form-select">
                    <option value="1" {{ old('is_active', $challenge->is_active ?? true) ? 'selected' : '' }}>نعم</option>
                    <option value="0" {{ old('is_active', $challenge->is_active ?? true) ? '' : 'selected' }}>لا</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">أقصى عدد إنجاز</label>
                <input type="number" name="max_completions" class="form-control" value="{{ old('max_completions', $challenge->max_completions ?? 0) }}" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">الوصف</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $challenge->description ?? '') }}</textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">تاريخ البداية</label>
                <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $challenge->starts_at ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">تاريخ النهاية</label>
                <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $challenge->ends_at ?? '') }}">
            </div>
        </div>

        <hr>
        <h4>الشروط</h4>
        <div id="conditions-container">
            @if($challenge)
                @foreach($challenge->conditions as $i => $cond)
                <div class="row mb-2 condition-row">
                    <div class="col-md-3"><input type="text" name="conditions[{{ $i }}][condition_type]" class="form-control" placeholder="النوع" value="{{ $cond->condition_type }}"></div>
                    <div class="col-md-2"><input type="text" name="conditions[{{ $i }}][operator]" class="form-control" placeholder="المعامل" value="{{ $cond->operator }}"></div>
                    <div class="col-md-4"><input type="text" name="conditions[{{ $i }}][value]" class="form-control" placeholder='{"min": 1}' value="{{ json_encode($cond->value) }}"></div>
                    <div class="col-md-2"><input type="number" name="conditions[{{ $i }}][logic_group]" class="form-control" placeholder="مجموعة" value="{{ $cond->logic_group }}"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger remove-row">×</button></div>
                </div>
                @endforeach
            @endif
        </div>
        <button type="button" class="btn btn-sm btn-secondary mb-3" id="add-condition">+ إضافة شرط</button>

        <hr>
        <h4>المكافآت</h4>
        <div id="rewards-container">
            @if($challenge)
                @foreach($challenge->rewards as $i => $reward)
                <div class="row mb-2 reward-row">
                    <div class="col-md-4"><input type="text" name="rewards[{{ $i }}][reward_type]" class="form-control" placeholder="النوع (points)" value="{{ $reward->reward_type }}"></div>
                    <div class="col-md-5"><input type="text" name="rewards[{{ $i }}][value]" class="form-control" placeholder='{"points": 10}' value="{{ json_encode($reward->value) }}"></div>
                    <div class="col-md-2"><input type="number" name="rewards[{{ $i }}][priority]" class="form-control" placeholder="الأولوية" value="{{ $reward->priority }}"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger remove-row">×</button></div>
                </div>
                @endforeach
            @endif
        </div>
        <button type="button" class="btn btn-sm btn-secondary mb-3" id="add-reward">+ إضافة مكافأة</button>

        <hr>
        <button type="submit" class="btn btn-primary">{{ $challenge ? 'تحديث' : 'إنشاء' }}</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let condIdx = {{ $challenge ? $challenge->conditions->count() : 0 }};
    let rewIdx = {{ $challenge ? $challenge->rewards->count() : 0 }};

    document.getElementById('add-condition')?.addEventListener('click', function() {
        const html = `<div class="row mb-2 condition-row">
            <div class="col-md-3"><input type="text" name="conditions[${condIdx}][condition_type]" class="form-control" placeholder="النوع"></div>
            <div class="col-md-2"><input type="text" name="conditions[${condIdx}][operator]" class="form-control" placeholder="المعامل" value="gte"></div>
            <div class="col-md-4"><input type="text" name="conditions[${condIdx}][value]" class="form-control" placeholder='{"min": 1}'></div>
            <div class="col-md-2"><input type="number" name="conditions[${condIdx}][logic_group]" class="form-control" placeholder="مجموعة"></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-row">×</button></div>
        </div>`;
        document.getElementById('conditions-container').insertAdjacentHTML('beforeend', html);
        condIdx++;
    });

    document.getElementById('add-reward')?.addEventListener('click', function() {
        const html = `<div class="row mb-2 reward-row">
            <div class="col-md-4"><input type="text" name="rewards[${rewIdx}][reward_type]" class="form-control" placeholder="النوع (points)"></div>
            <div class="col-md-5"><input type="text" name="rewards[${rewIdx}][value]" class="form-control" placeholder='{"points": 10}'></div>
            <div class="col-md-2"><input type="number" name="rewards[${rewIdx}][priority]" class="form-control" placeholder="الأولوية"></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-row">×</button></div>
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
