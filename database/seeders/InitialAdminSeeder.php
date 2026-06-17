<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ════════════════════════════════════════════════════════════════
 *  Seeder: إنشاء أول حساب أدمن
 * ════════════════════════════════════════════════════════════════
 *
 *  ⚠️ يُنفّذ مرة واحدة فقط:
 *    php artisan db:seed --class=InitialAdminSeeder
 *
 *  القيم الافتراضية:
 *  - username: admin
 *  - password: admin123 (يجب تغييرها فوراً بعد الدخول الأول)
 */
class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('INITIAL_ADMIN_USERNAME', 'admin');
        $password = env('INITIAL_ADMIN_PASSWORD', 'admin123');
        $fullName = env('INITIAL_ADMIN_NAME', 'المسؤول الرئيسي');

        if (Admin::where('username', $username)->exists()) {
            $this->command->info("الأدمن {$username} موجود بالفعل.");
            return;
        }

        Admin::create([
            'username' => $username,
            'password' => Hash::make($password),
            'full_name' => $fullName,
        ]);

        $this->command->info("تم إنشاء حساب الأدمن: {$username} / {$password}");
        $this->command->warn('⚠️  غيّر كلمة المرور فوراً بعد الدخول الأول!');
    }
}
