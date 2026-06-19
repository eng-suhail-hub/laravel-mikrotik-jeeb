<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RouterController;
use App\Http\Controllers\Admin\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (لوحة تحكم الأدمن)
|--------------------------------------------------------------------------
*/

// صفحة تسجيل الدخول (متاحة للجميع)
Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

// كل المسارات التالية محمية بـ AdminAuth middleware
Route::middleware('admin.auth')->prefix('admin')->name('admin.')->group(function () {

    // تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // لوحة المعلومات
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');

    // إدارة الراوتر (إدخال بيانات الاتصال + اختبار)
    Route::prefix('router')->name('router.')->group(function () {
        Route::get('/', [RouterController::class, 'index'])->name('index');
        Route::post('/save', [RouterController::class, 'save'])->name('save');
        Route::post('/test', [RouterController::class, 'test'])->name('test');
        Route::post('/connect', [RouterController::class, 'connectAndSave'])->name('connect');
    });

    // إدارة الباقات
    Route::post('profiles/sync', [ProfileController::class, 'syncFromMikrotik'])->name('profiles.sync');
    Route::resource('profiles', ProfileController::class)->except(['show', 'create', 'store']);

    // إدارة العمليات
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/pending', [TransactionController::class, 'pending'])->name('pending');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::post('/{transaction}/activate', [TransactionController::class, 'manualActivate'])->name('activate');
        Route::post('/{transaction}/assign-profile', [TransactionController::class, 'assignProfile'])->name('assignProfile');
        Route::post('/{transaction}/retry', [TransactionController::class, 'retry'])->name('retry');
    });
});

// الصفحة الرئيسية → إعادة توجيه للوحة الأدمن
Route::get('/', function () {
    return redirect()->route('admin.login');
});
