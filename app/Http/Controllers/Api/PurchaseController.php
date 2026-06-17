<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم طلبات الشراء (Flutter App)
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ السلوك المهم:
 *  1. العميل يطلب شراء باقة
 *  2. Laravel يُنشئ Transaction في حالة "pending_match"
 *  3. يُرجع رسالة "قيد المعالجة" — لا ينتظر الـ Webhook
 *  4. عندما يصل Webhook الدفع، يُطابق ويُجدول توليد الكرت
 *
 *  هذا يحقق:
 *  - استجابة فورية لتطبيق Flutter
 *  - فصل منطق الدفع عن منطق التوليد
 *  - العمل بشكل صحيح حتى لو انقطع الراوتر مؤقتاً
 */
class PurchaseController extends Controller
{
    /**
     * عرض الباقات المتاحة في تطبيق Flutter
     */
    public function profiles(): JsonResponse
    {
        $profiles = Profile::active()
            ->orderBy('price')
            ->get(['id', 'name', 'price', 'duration_hours', 'speed_limit']);

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    /**
     * إنشاء طلب شراء جديد
     *
     * ⚠️ لا يتحقق من الدفع هنا — فقط يستقبل طلب العميل.
     *    التحقق الفعلي يحدث عند استقبال Webhook من محفظة جيب.
     */
    public function purchase(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'profile_id' => 'required|exists:profiles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // التحقق من أن الباقة نشطة
        $profile = Profile::active()->find($data['profile_id']);
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'الباقة غير متاحة حالياً.',
            ], 400);
        }

        // إنشاء العملية في حالة pending_match
        // بانتظار وصول Webhook الدفع للمطابقة
        $transaction = Transaction::create([
            'user_id' => $data['user_id'],
            'profile_id' => $data['profile_id'],
            'status' => Transaction::STATUS_PENDING_MATCH,
        ]);

        return response()->json([
            'success' => true,
            // ⚠️ رسالة ذكية — لا خطأ، فقط "قيد المعالجة"
            'message' => 'تم استلام طلبك وهو قيد المعالجة والتوليد. سيتم إشعارك فور توفر الكرت.',
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'profile' => [
                    'name' => $profile->name,
                    'price' => $profile->price,
                ],
            ],
        ], 201);
    }

    /**
     * ⚠️ محذوف عمداً: endpoint "check status"
     * لأن التطبيق سيستقبل إشعار FCM عند توليد الكرت
     * (يُرسل من Job آخر — يمكن إضافته لاحقاً)
     */
}
