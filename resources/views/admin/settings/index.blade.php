@extends('admin.layouts.app')

@section('title', 'إعدادات النظام')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-gear" style="color:var(--accent)"></i> إعدادات النظام</h2>
    <p>إدارة إعدادات النظام العامة والتكوين</p>
</div>

<div class="card anim-fade-in">
    <div class="card-header">
        <i class="bi bi-sliders" style="color:var(--accent)"></i>
        الإعدادات العامة
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <div style="display:flex;flex-direction:column;gap:16px;">
                @foreach($settings as $setting)
                    <div>
                        <label style="font-size:13px;font-weight:600;color:var(--fg);margin-bottom:4px;display:block;">
                            {{ $setting->description ?: $setting->key }}
                        </label>
                        <input type="text" name="settings[{{ $setting->key }}]" class="form-control-m"
                               value="{{ old("settings.{$setting->key}", $setting->value) }}">
                        <div style="font-size:11px;color:var(--muted);margin-top:2px;font-family:'SF Mono',ui-monospace,Menlo,monospace;">
                            {{ $setting->key }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-m btn-m-primary">
                    <i class="bi bi-save"></i> حفظ الإعدادات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection