<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RouterSetting;
use App\Services\MikroTikService;
use Illuminate\Http\Request;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم إدارة الراوتر (إدخال بيانات الاتصال + اختبار)
 * ════════════════════════════════════════════════════════════════
 *
 *  هذا المتحكم هو "البوابة الأولى" للنظام.
 *  لا يمكن توليد كروت قبل نجاح الاتصال بالراوتر.
 */
class RouterController extends Controller
{
    /**
     * عرض صفحة إعدادات الراوتر الحالية
     */
    public function index()
    {
        $setting = RouterSetting::current();
        return view('admin.router.settings', compact('setting'));
    }

    /**
     * حفظ بيانات الاتصال (بدون اختبار)
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'host' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $setting = RouterSetting::current();
        $setting->update(array_merge($data, [
            'is_connected' => false,
        ]));

        return redirect()
            ->route('admin.router.index')
            ->with('success', 'تم حفظ بيانات الراوتر. يُرجى اختبار الاتصال قبل المتابعة.');
    }

    /**
     * اختبار الاتصال بالبيانات المدخلة (قبل الحفظ)
     *
     * يُستخدم AJAX من الواجهة لإظهار النتيجة فوراً.
     */
    public function test(Request $request)
    {
        $data = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $service = app(MikroTikService::class);
        $result = $service->testConnection(
            $data['host'],
            (int) $data['port'],
            $data['username'],
            $data['password']
        );

        return response()->json($result);
    }

    /**
     * اختبار الاتصال + حفظ معلومات الراوتر عند النجاح
     *
     * هذه هي النقطة التي "يُفعَّل" بعدها بقية النظام.
     */
    public function connectAndSave(Request $request)
    {
        $data = $request->validate([
            'host' => 'required|string|max:100',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        $service = app(MikroTikService::class);
        $result = $service->testConnection(
            $data['host'],
            (int) $data['port'],
            $data['username'],
            $data['password']
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->withErrors(['connection' => $result['error']]);
        }

        // ✅ نجح الاتصال → حفظ البيانات + معلومات الراوتر
        $setting = RouterSetting::current();
        $setting->update(array_merge($data, [
            'is_connected' => true,
            'last_test_at' => now(),
            'router_identity' => $result['info']['identity'] ?? null,
            'routeros_version' => $result['info']['version'] ?? null,
            'board_name' => $result['info']['board'] ?? null,
        ]));

        return redirect()
            ->route('admin.router.index')
            ->with('success', 'تم الاتصال بالراوتر بنجاح! النظام جاهز للعمل.');
    }
}
