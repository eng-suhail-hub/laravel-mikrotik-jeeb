<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\PointsBalance;
use App\Models\RawWebhook;
use App\Models\RouterSetting;
use App\Models\SystemSetting;
use App\Models\Transaction;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم لوحة المعلومات الرئيسية (Dashboard)
 * ════════════════════════════════════════════════════════════════
 */
class DashboardController extends Controller
{
    /**
     * الصفحة الرئيسية للوحة الأدمن
     */
    public function index()
    {
        $stats = [
            // حالة الراوتر
            'router_connected' => RouterSetting::current()->is_connected,

            // عدادات العمليات
            'pending_count' => Transaction::pending()->count(),
            'processing_count' => Transaction::where('status', Transaction::STATUS_PROCESSING)->count(),
            'completed_today' => Transaction::completed()
                ->whereDate('card_generated_at', today())
                ->count(),
            'failed_count' => Transaction::failed()->count(),

            // إجمالي الـ Webhooks المستلمة
            'webhooks_total' => RawWebhook::count(),
            'webhooks_today' => RawWebhook::whereDate('created_at', today())->count(),

            // V2: إحصائيات النقاط
            'total_points_balance' => PointsBalance::sum('balance'),
            'total_users_with_points' => PointsBalance::where('balance', '>', 0)->count(),
            'point_price' => SystemSetting::getValue('point_price_yri', '10'),

            // V2: التحديات النشطة
            'active_challenges' => Challenge::active()->count(),

            // V2: عمليات V2 اليوم
            'v2_transactions_today' => Transaction::where('type', '!=', Transaction::TYPE_CARD_PURCHASE)
                ->whereDate('created_at', today())->count(),
        ];

        // آخر 5 عمليات
        $recentTransactions = Transaction::with(['profile', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // آخر 5 Webhooks (للسجل المالي)
        $recentWebhooks = RawWebhook::latest()->limit(5)->get();

        return view('admin.dashboard.index', compact('stats', 'recentTransactions', 'recentWebhooks'));
    }
}
