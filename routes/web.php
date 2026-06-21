<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BatchGenerationController;
use App\Http\Controllers\Admin\ChallengeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\PointsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RouterController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\VoucherController;
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

    // إدارة التحديات
    Route::resource('challenges', ChallengeController::class)->except(['show']);

    // إدارة العمليات
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/pending', [TransactionController::class, 'pending'])->name('pending');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::post('/{transaction}/activate', [TransactionController::class, 'manualActivate'])->name('activate');
        Route::post('/{transaction}/assign-profile', [TransactionController::class, 'assignProfile'])->name('assignProfile');
        Route::post('/{transaction}/retry', [TransactionController::class, 'retry'])->name('retry');
    });

    // V2: إدارة النقاط
    Route::prefix('points')->name('points.')->group(function () {
        Route::get('/', [PointsController::class, 'index'])->name('index');
        Route::get('/transactions', [PointsController::class, 'transactions'])->name('transactions');
        Route::post('/adjust/{user}', [PointsController::class, 'adjust'])->name('adjust');
    });

    // V2: التوليد الجماعي
    Route::prefix('batch-generations')->name('batch.')->group(function () {
        Route::get('/', [BatchGenerationController::class, 'index'])->name('index');
        Route::post('/generate', [BatchGenerationController::class, 'generate'])->name('generate');
        Route::get('/{id}/progress', [BatchGenerationController::class, 'progress'])->name('progress');
    });

    // V2: طباعة القسائم
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/preview', [VoucherController::class, 'preview'])->name('preview');
        Route::post('/print', [VoucherController::class, 'print'])->name('print');
    });

    // V2: صيانة الراوتر
    Route::prefix('maintenance')->name('maintenance.')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');
        Route::post('/{action}', [MaintenanceController::class, 'execute'])->name('execute');
    });

    // V2: إعدادات النظام
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])->name('index');
        Route::put('/', [SystemSettingController::class, 'update'])->name('update');
    });
});

// الصفحة الرئيسية → إعادة توجيه للوحة الأدمن
Route::get('/', function () {
    return redirect()->route('admin.login');
});
