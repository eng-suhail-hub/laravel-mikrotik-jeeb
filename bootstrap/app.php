<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\LocalhostOnly;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * ════════════════════════════════════════════════════════════════
 *  Bootstrap (Laravel 11)
 * ════════════════════════════════════════════════════════════════
 *
 *  تسجيل Middleware المخصصة:
 *  - admin.auth: حماية لوحة الأدمن
 *  - localhost.only: قصر الـ Webhook على Localhost
 */

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // تسجيل Middleware المخصصة بأسماء مستعارة
        $middleware->alias([
            'admin.auth' => AdminAuth::class,
            'localhost.only' => LocalhostOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
