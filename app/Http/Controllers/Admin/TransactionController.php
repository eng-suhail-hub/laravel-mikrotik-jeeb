<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMikrotikCardJob;
use App\Models\Profile;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CardGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ════════════════════════════════════════════════════════════════
 *  متحكم العمليات (Transactions) - لوحة الأدمن
 * ════════════════════════════════════════════════════════════════
 *
 *  الوظائف:
 *  1. عرض العمليات المعلقة (Pending / Manual Pending)
 *  2. التفعيل اليدوي للأدمن (توليد كرت دون انتظار الـ Webhook)
 *  3. عرض السجل الكامل للعمليات
 */
class TransactionController extends Controller
{
    /**
     * الصفحة الرئيسية: العمليات المعلقة
     */
    public function pending(Request $request)
    {
        $query = Transaction::with(['profile', 'user'])
            ->pending()
            ->latest();

        // فلترة بالاسم/الهاتف/المرجع
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('webhook_phone', 'like', "%{$search}%")
                  ->orWhere('webhook_full_name', 'like', "%{$search}%")
                  ->orWhere('jeeb_reference', 'like', "%{$search}%");
            });
        }

        $transactions = $query->paginate(20)->withQueryString();

        // الباقات النشطة للتفعيل اليدوي
        $profiles = Profile::active()->orderBy('price')->get();

        return view('admin.transactions.pending', compact('transactions', 'profiles'));
    }

    /**
     * السجل الكامل لكل العمليات
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['profile', 'user', 'activatedByAdmin'])
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate(30)->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }

    /**
     * تفاصيل عملية واحدة
     */
    public function show(Transaction $transaction)
    {
        $transaction->load(['profile', 'user', 'rawWebhook', 'activatedByAdmin']);
        return view('admin.transactions.show', compact('transaction'));
    }

    /**
     * التفعيل اليدوي لعملية معلقة
     *
     * يولّد الكرت مباشرة في الراوتر ويسجل الأدمن كمُفعّل.
     */
    public function manualActivate(Request $request, Transaction $transaction)
    {
        // ⚠️ فقط العمليات في حالة manual_pending أو pending_match يمكن تفعيلها
        if (!in_array($transaction->status, [
            Transaction::STATUS_PENDING_MATCH,
            Transaction::STATUS_MANUAL_PENDING,
        ], true)) {
            return back()->withErrors(['error' => 'هذه العملية لا يمكن تفعيلها يدوياً.']);
        }

        // محاولة ربطها بمستخدم إذا لم تكن مرتبطة
        if (!$transaction->user_id && $transaction->webhook_phone && $transaction->webhook_full_name) {
            $user = User::findByMatch(
                $transaction->webhook_full_name,
                $transaction->webhook_phone
            );

            if ($user) {
                $transaction->update(['user_id' => $user->id]);
            }
        }

        DB::beginTransaction();

        try {
            $transaction->update([
                'status' => Transaction::STATUS_PROCESSING,
                'activated_by_admin_id' => Auth::guard('admin')->id(),
            ]);

            // توليد الكرت مباشرة (لا نُدخله في Queue لأن الأدمن ينتظر النتيجة)
            $generator = app(CardGeneratorService::class);
            $updated = $generator->generate($transaction);

            DB::commit();

            return redirect()
                ->route('admin.transactions.show', $transaction)
                ->with('success', 'تم توليد الكرت بنجاح! البيانات: ' . $updated->mikrotik_username);

        } catch (\Throwable $e) {
            DB::rollBack();

            $transaction->update([
                'status' => Transaction::STATUS_FAILED,
                'failure_reason' => 'تفعيل يدوي فاشل: ' . $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'فشل توليد الكرت: ' . $e->getMessage()]);
        }
    }

    /**
     * تعيين باقة لعملية معلقة (قبل التفعيل اليدوي)
     */
    public function assignProfile(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'profile_id' => 'required|exists:profiles,id',
        ]);

        $transaction->update(['profile_id' => $data['profile_id']]);

        return back()->with('success', 'تم تعيين الباقة للعميلة. يمكنك الآن تفعيلها.');
    }

    /**
     * إعادة محاولة عملية فاشلة (يُدخلها في Queue مجدداً)
     */
    public function retry(Transaction $transaction)
    {
        if ($transaction->status !== Transaction::STATUS_FAILED) {
            return back()->withErrors(['error' => 'يمكن إعادة المحاولة فقط للعمليات الفاشلة.']);
        }

        $transaction->update([
            'status' => Transaction::STATUS_PROCESSING,
            'failure_reason' => null,
        ]);

        GenerateMikrotikCardJob::dispatch($transaction->id);

        return back()->with('success', 'تم إعادة إدخال العملية في الطابور.');
    }
}
