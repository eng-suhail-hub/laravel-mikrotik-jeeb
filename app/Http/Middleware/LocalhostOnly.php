<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ════════════════════════════════════════════════════════════════
 *  Middleware: قصر الوصول على Localhost + التحقق من API Key
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ طبقاً للمواصفات: الـ Emulator و Laravel على نفس الجهاز الفيزيائي.
 *  لذلك:
 *  1. يجب أن يكون الطلب من 127.0.0.1 أو ::1 فقط
 *  2. يجب أن يحتوي على X-Jeeb-Secret يطابق المفتاح في config/jeeb.php
 *
 *  هذا الـ Middleware يُطبَّق على route الـ Webhook فقط.
 */
class LocalhostOnly
{
    /**
     * معالجة الطلب
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        // ⚠️ التحقق 1: الطلب من Localhost فقط
        if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
            Log::warning('Webhook rejected: non-localhost IP', [
                'ip' => $clientIp,
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'ممنوع الوصول من خارج الجهاز المحلي.',
            ], 403);
        }

        // ⚠️ التحقق 2: المفتاح السري في الـ Header
        $providedKey = $request->header('X-Jeeb-Secret');
        $expectedKey = config('jeeb.webhook_secret');

        if (empty($expectedKey) || $expectedKey === 'change-this-strong-secret-key') {
            // ⚠️ تنبيه في الـ logs إذا لم يُغيّر المفتاح الافتراضي
            Log::error('JEEB_WEBHOOK_SECRET is still the default value — please change it in .env');
        }

        if (empty($providedKey) || !hash_equals((string) $expectedKey, (string) $providedKey)) {
            Log::warning('Webhook rejected: invalid secret key', [
                'ip' => $clientIp,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'مفتاح الأمان غير صحيح.',
            ], 401);
        }

        return $next($request);
    }
}
