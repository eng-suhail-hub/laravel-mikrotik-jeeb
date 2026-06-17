<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RawWebhook;
use App\Models\RouterSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

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
            'pending_count'    => Transaction::pending()->count(),
            'processing_count' => Transaction::where('status', Transaction::STATUS_PROCESSING)->count(),
            'completed_today'  => Transaction::completed()
                ->whereDate('card_generated_at', today())
                ->count(),
            'failed_count'     => Transaction::failed()->count(),

            // إجمالي الـ Webhooks المستلمة
            'webhooks_total'   => RawWebhook::count(),
            'webhooks_today'   => RawWebhook::whereDate('created_at', today())->count(),
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
