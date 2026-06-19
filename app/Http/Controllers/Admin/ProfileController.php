<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Throwable;

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
    public function index(MikroTikService $mikrotikService)
    {
        if (Profile::count() === 0) {
            try {
                $count = $this->performSync($mikrotikService);
                if ($count > 0) {
                    session()->flash('success', "تمت المزامنة الأولية بنجاح: {$count} باقة.");
                }
            } catch (Throwable $e) {
                session()->flash('warning', 'تعذر المزامنة الأولية للباقات. يرجى التأكد من اتصال الراوتر. الخطأ: ' . $e->getMessage());
            }
        }

        $profiles = Profile::orderBy('price')->paginate(20);
        return view('admin.profiles.index', compact('profiles'));
    }

    /**
     * مزامنة الباقات من المايكروتك يدوياً
     */
    public function syncFromMikrotik(MikroTikService $mikrotikService)
    {
        try {
            $count = $this->performSync($mikrotikService);
            return back()->with('success', "تمت مزامنة {$count} باقة من المايكروتك بنجاح.");
        } catch (Throwable $e) {
            return back()->with('error', 'فشلت المزامنة. تأكد من إعدادات الراوتر. الخطأ: ' . $e->getMessage());
        }
    }

    /**
     * وظيفة المزامنة الأساسية
     */
    private function performSync(MikroTikService $mikrotikService): int
    {
        $mikrotikProfiles = $mikrotikService->getUserManagerProfiles();
        $count = 0;

        foreach ($mikrotikProfiles as $mp) {
            $validity = $mp['validity'] ?? '';
            $hours = $this->parseMikrotikDurationToHours($validity);

            Profile::updateOrCreate(
                ['mikrotik_profile_name' => $mp['name']],
                [
                    'name' => $mp['name'], // Default name to the mikrotik name, admin can edit it later
                    'price' => $mp['price'],
                    'duration_hours' => $hours,
                    'speed_limit' => $mp['speed_limit'],
                    'is_active' => true, // Ensure it's active when synced
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * تحويل صيغ المايكروتك للوقت (مثل 2w4d12h) إلى ساعات
     */
    private function parseMikrotikDurationToHours(string $duration): int
    {
        if (empty($duration)) {
            return 24; // Default to 24 hours if empty
        }

        // It can also be a simple number which means seconds.
        if (is_numeric($duration)) {
            return max(1, (int) round($duration / 3600));
        }

        preg_match_all('/(\d+)([wdhms])/', strtolower($duration), $matches, PREG_SET_ORDER);
        
        if (empty($matches)) {
            return 24;
        }

        $totalHours = 0;
        foreach ($matches as $match) {
            $val = (int) $match[1];
            switch ($match[2]) {
                case 'w': $totalHours += $val * 168; break;
                case 'd': $totalHours += $val * 24; break;
                case 'h': $totalHours += $val; break;
                case 'm': $totalHours += $val / 60; break;
                case 's': $totalHours += $val / 3600; break;
            }
        }

        return max(1, (int) round($totalHours));
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
