<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم تسجيل دخول الأدمن
 * ════════════════════════════════════════════════════════════════
 *
 *  تسجيل دخول بسيط بـ Session Guard (لا Sanctum هنا).
 */
class AuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    /**
     * معالجة تسجيل الدخول
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // تحديث وقت آخر دخول
            $admin = Auth::guard('admin')->user();
            $admin->update(['last_login_at' => now()]);

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'username' => 'بيانات الدخول غير صحيحة.',
        ])->onlyInput('username');
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * إنشاء أول حساب أدمن (يُستخدم مرة واحدة فقط)
     * يُستدعى من سطر الأوامر أو من seed
     */
    public static function createInitialAdmin(string $username, string $password, ?string $fullName = null): Admin
    {
        return Admin::create([
            'username' => $username,
            'password' => Hash::make($password),
            'full_name' => $fullName,
        ]);
    }
}
