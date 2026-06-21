<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::orderBy('key')->get();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $keys = SystemSetting::pluck('key')->toArray();
        $rules = [];
        foreach ($keys as $key) {
            $rules["settings.{$key}"] = 'nullable|string';
        }

        $data = $request->validate($rules);

        foreach ($data['settings'] ?? [] as $key => $value) {
            SystemSetting::setValue($key, $value ?? '');
        }

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
