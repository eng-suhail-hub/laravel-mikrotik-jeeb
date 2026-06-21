@extends('admin.layouts.app')

@section('title', 'التوليد الجماعي')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-layers" style="color:var(--accent)"></i> التوليد الجماعي</h2>
    <p>توليد بطاقات بكميات كبيرة دفعة واحدة</p>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-plus-circle" style="color:var(--accent)"></i>
                توليد بطاقات جديدة
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.batch.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">الباقة</label>
                        <select name="profile_id" class="form-control-m" required>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }} ({{ number_format($profile->price) }} ريال)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">العدد</label>
                        <input type="number" name="quantity" class="form-control-m" min="1" max="1000" required
                               placeholder="عدد البطاقات">
                    </div>
                    <div class="mb-3">
                        <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">وضع بيانات الدخول</label>
                        <select name="credential_mode" class="form-control-m">
                            <option value="match">متطابقة (اسم المستخدم = كلمة المرور)</option>
                            <option value="separate">منفصلة</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">طول اسم المستخدم</label>
                            <input type="number" name="username_length" class="form-control-m" value="10" min="6" max="32">
                        </div>
                        <div class="col-6">
                            <label style="font-size:12px;font-weight:600;color:var(--muted);margin-bottom:4px;display:block;">بادئة</label>
                            <input type="text" name="username_prefix" class="form-control-m" maxlength="10" placeholder="NET-">
                        </div>
                    </div>
                    <button type="submit" class="btn-m btn-m-primary" style="width:100%;justify-content:center;">
                        <i class="bi bi-lightning-charge"></i> بدء التوليد
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card anim-fade-in">
            <div class="card-header">
                <i class="bi bi-clock-history" style="color:var(--accent)"></i>
                العمليات السابقة
            </div>
            <div class="card-body p-0">
                @if($batches->count())
                    <div class="table-responsive">
                        <table class="table-clean">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الباقة</th>
                                    <th>العدد</th>
                                    <th>تم</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batches as $batch)
                                    <tr>
                                        <td style="font-size:13px;">{{ $batch->created_at }}</td>
                                        <td style="font-weight:500;">{{ $batch->profile?->name }}</td>
                                        <td style="font-feature-settings:'tnum';">{{ $batch->quantity }}</td>
                                        <td style="font-feature-settings:'tnum';font-weight:600;color:var(--success);">{{ $batch->generated_count }}</td>
                                        <td>
                                            @switch($batch->status)
                                                @case('pending')
                                                    <span class="badge-status bg-secondary">انتظار</span>
                                                    @break
                                                @case('processing')
                                                    <span class="badge-status bg-info">قيد التنفيذ</span>
                                                    @break
                                                @case('completed')
                                                    <span class="badge-status bg-success">مكتمل</span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge-status bg-danger">فشل</span>
                                                    @break
                                                @case('partially_completed')
                                                    <span class="badge-status bg-warning">جزئي</span>
                                                    @break
                                            @endswitch
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-clock-history"></i>
                        <p>لا توجد عمليات توليد سابقة.</p>
                    </div>
                @endif
            </div>
        </div>

        @if(method_exists($batches, 'links'))
            <div class="mt-3">{{ $batches->links() }}</div>
        @endif
    </div>
</div>
@endsection