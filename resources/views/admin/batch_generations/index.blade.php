@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1>التوليد الجماعي</h1>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">توليد بطاقات جديدة</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.batch.generate') }}">
                        @csrf
                        <div class="mb-3">
                            <label>الباقة</label>
                            <select name="profile_id" class="form-select" required>
                                @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }} ({{ number_format($profile->price) }} ريال)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>العدد</label>
                            <input type="number" name="quantity" class="form-control" min="1" max="1000" required>
                        </div>
                        <div class="mb-3">
                            <label>وضع بيانات الدخول</label>
                            <select name="credential_mode" class="form-select">
                                <option value="match">متطابقة (اسم المستخدم = كلمة المرور)</option>
                                <option value="separate">منفصلة</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>طول اسم المستخدم</label>
                            <input type="number" name="username_length" class="form-control" value="10" min="6" max="32">
                        </div>
                        <div class="mb-3">
                            <label>بادئة اسم المستخدم</label>
                            <input type="text" name="username_prefix" class="form-control" maxlength="10" placeholder="مثال: NET-">
                        </div>
                        <button type="submit" class="btn btn-primary">توليد</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">العمليات السابقة</div>
                <div class="card-body">
                    <table class="table">
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
                                <td>{{ $batch->created_at }}</td>
                                <td>{{ $batch->profile?->name }}</td>
                                <td>{{ $batch->quantity }}</td>
                                <td>{{ $batch->generated_count }}</td>
                                <td>
                                    @switch($batch->status)
                                        @case('pending') <span class="badge bg-warning">انتظار</span> @break
                                        @case('processing') <span class="badge bg-info">قيد التنفيذ</span> @break
                                        @case('completed') <span class="badge bg-success">مكتمل</span> @break
                                        @case('failed') <span class="badge bg-danger">فشل</span> @break
                                        @case('partially_completed') <span class="badge bg-warning">جزئي</span> @break
                                    @endswitch
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
