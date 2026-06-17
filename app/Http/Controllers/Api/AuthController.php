<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم مصادقة العميل (Flutter App)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ لا يوجد تسجيل دخول متكرر.
 *  تسجيل مرة واحدة فقط: الاسم الرباعي + رقم الهاتف.
 *
 *  الهدف: مطابقة بيانات العميل لاحقاً مع إشعار الدفع من محفظة جيب.
 *
 *  رسالة التنبيه (registration_warning) تُرجع في كل استجابة
 *  ليعرضها تطبيق Flutter كـ Dialog قبل تأكيد التسجيل.
 */
class AuthController extends Controller
{
    /**
     * تسجيل حساب جديد (لمرة واحدة فقط)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => [
                'required',
                'string',
                'min:8',
                'max:255',
                // ⚠️ يجب أن يحتوي على 4 كلمات على الأقل (اسم رباعي)
                function ($attribute, $value, $fail) {
                    $wordCount = count(preg_split('/\s+/u', trim($value)));
                    if ($wordCount < 4) {
                        $fail('يجب إدخال الاسم الرباعي (4 كلمات على الأقل).');
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+?967|0)?7\d{8}$/',
            ],
            'device_token' => 'nullable|string',
        ], [
            'full_name.required' => 'الاسم الرباعي مطلوب.',
            'phone.regex' => 'رقم الهاتف يجب أن يكون يمني صحيح (7XXXXXXXX).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة.',
                'errors' => $validator->errors(),
                'warning' => config('jeeb.registration_warning'),
            ], 422);
        }

        $data = $validator->validated();

        // التحقق من عدم وجود حساب سابق
        $existing = User::where('phone', $data['phone'])->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الرقم مسجل مسبقاً.',
                'warning' => config('jeeb.registration_warning'),
            ], 409);
        }

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح.',
            'warning' => config('jeeb.registration_warning'),
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
            ],
        ], 201);
    }

    /**
     * ⚠️ لا يوجد endpoint لـ "login" — التسجيل لمرة واحدة فقط.
     *     هذا متعمد حسب المواصفات.
     */
}
