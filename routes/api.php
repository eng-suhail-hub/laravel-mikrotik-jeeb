<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\V2Controller;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (تطبيق Flutter + Webhook Emulator)
|--------------------------------------------------------------------------
*/

// ════════════════════════════════════════════════════════════════
// نقاط نهاية تطبيق Flutter (عامة)
// ════════════════════════════════════════════════════════════════

// تسجيل العميل (لمرة واحدة فقط)
Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');

// عرض الباقات
Route::get('/profiles', [PurchaseController::class, 'profiles'])->name('api.profiles');

// إنشاء طلب شراء
Route::post('/purchase', [PurchaseController::class, 'purchase'])->name('api.purchase');

// ════════════════════════════════════════════════════════════════
// Webhook إشعارات محفظة جيب (محمي بـ LocalhostOnly + API Key)
// ════════════════════════════════════════════════════════════════

Route::middleware('localhost.only')->group(function () {
    Route::post('/webhook/jeeb', [WebhookController::class, 'receive'])->name('api.webhook.jeeb');
});

// ⚠️ ملاحظة: المسار /api/webhook/jeeb يجب أن يكون مُعداً في الـ Emulator
// مع الـ Header: X-Jeeb-Secret: <your-secret-key>

// ════════════════════════════════════════════════════════════════
// نقاط نهاية V2 (Instant Delivery + نقاط + تحديات)
// ════════════════════════════════════════════════════════════════

Route::prefix('v2')->name('api.v2.')->middleware(['check.banned'])->group(function () {
    Route::post('/verify-transaction', [V2Controller::class, 'verifyTransaction'])->name('verify');
    Route::get('/network-status', [V2Controller::class, 'networkStatus'])->name('status');
    Route::get('/app-config', [V2Controller::class, 'appConfig'])->name('config');
});
