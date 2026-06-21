@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>إعدادات النظام</h1>

    <div class="card mt-4">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                @foreach($settings as $setting)
                <div class="mb-3">
                    <label class="form-label">{{ $setting->description ?: $setting->key }}</label>
                    <input type="text" name="settings[{{ $setting->key }}]" class="form-control" value="{{ old("settings.{$setting->key}", $setting->value) }}">
                </div>
                @endforeach

                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
            </form>
        </div>
    </div>
</div>
@endsection
