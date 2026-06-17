<?php

/**
 * ════════════════════════════════════════════════════════════════
 *  إعدادات المصادقة
 * ════════════════════════════════════════════════════════════════
 *
 *  نُعرّف Guard باسم 'admin' يستخدم موديل App\Models\Admin.
 *  هذا الـ Guard يُستخدم حصرياً في لوحة التحكم.
 */

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // ⚠️ Guard خاص بلوحة الأدمن — منفصل عن العملاء
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],

    'passwords' => [
        // لا حاجة لـ password reset (لا يوجد تسجيل دخول متكرر)
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
