<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\VoucherTheme;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'point_price_yri'],
            ['value' => '10', 'description' => 'سعر النقطة الواحدة بالريال اليمني']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => 'false', 'description' => 'وضع الصيانة للشبكة']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'jeeb_wallet_phone'],
            ['value' => '', 'description' => 'رقم محفظة جيب الخاصة بصاحب الشبكة']
        );
        SystemSetting::firstOrCreate(
            ['key' => 'jeeb_wallet_full_name'],
            ['value' => '', 'description' => 'الاسم الرباعي لحساب صاحب الشبكة في محفظة جيب']
        );

        SystemSetting::firstOrCreate(
            ['key' => 'network_name'],
            ['value' => 'شبكتي', 'description' => 'اسم الشبكة الذي يظهر في قسائم الطباعة']
        );

        VoucherTheme::firstOrCreate(
            ['blade_view' => 'admin.vouchers.themes.classic'],
            ['name' => 'كلاسيك', 'is_default' => true]
        );
        VoucherTheme::firstOrCreate(
            ['blade_view' => 'admin.vouchers.themes.modern'],
            ['name' => 'مودرن', 'is_default' => false]
        );
    }
}
