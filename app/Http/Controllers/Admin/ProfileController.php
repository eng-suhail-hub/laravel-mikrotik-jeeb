<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم إدارة الباقات (Profiles)
 * ════════════════════════════════════════════════════════════════
 *
 *  كل صف يمثل باقة للبيع في Flutter، مرتبطة بـ Profile
 *  موجود فعلياً في User Manager داخل الراوتر.
 */
class ProfileController extends Controller
{
    /**
     * عرض قائمة الباقات
     */
    public function index()
    {
        $profiles = Profile::orderBy('price')->paginate(20);
        return view('admin.profiles.index', compact('profiles'));
    }

    /**
     * صفحة إنشاء باقة جديدة
     */
    public function create()
    {
        return view('admin.profiles.create');
    }

    /**
     * حفظ باقة جديدة
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'mikrotik_profile_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'speed_limit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Profile::create($data);

        return redirect()
            ->route('admin.profiles.index')
            ->with('success', 'تم إنشاء الباقة بنجاح.');
    }

    /**
     * صفحة تعديل باقة
     */
    public function edit(Profile $profile)
    {
        return view('admin.profiles.edit', compact('profile'));
    }

    /**
     * تحديث باقة
     */
    public function update(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'mikrotik_profile_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'speed_limit' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $profile->update($data);

        return redirect()
            ->route('admin.profiles.index')
            ->with('success', 'تم تحديث الباقة.');
    }

    /**
     * حذف باقة (soft disable — لا يُحذف إذا لها عمليات)
     */
    public function destroy(Profile $profile)
    {
        if ($profile->transactions()->exists()) {
            // ⚠️ لا يمكن الحذف لوجود عمليات مرتبطة
            $profile->update(['is_active' => false]);
            return back()->with('warning', 'تم تعطيل الباقة بدلاً من حذفها لوجود عمليات مرتبطة.');
        }

        $profile->delete();
        return back()->with('success', 'تم حذف الباقة.');
    }
}
