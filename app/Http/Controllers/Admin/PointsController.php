<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointsBalance;
use App\Models\PointsTransaction;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    public function __construct(private PointsService $pointsService) {}

    public function index()
    {
        $balances = PointsBalance::with('user')
            ->orderBy('balance', 'desc')
            ->paginate(30);

        $recentTransactions = PointsTransaction::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.points.index', compact('balances', 'recentTransactions'));
    }

    public function transactions(Request $request)
    {
        $query = PointsTransaction::with('user')->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate(30)->withQueryString();

        return view('admin.points.transactions', compact('transactions'));
    }

    public function adjust(Request $request, User $user)
    {
        $data = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($user, $data) {
                if ($data['type'] === 'credit') {
                    $this->pointsService->credit($user, $data['amount'], $data['reason'], 'admin');
                } else {
                    $this->pointsService->debit($user, $data['amount'], $data['reason'], 'admin');
                }
            });

            return back()->with('success', 'تم تعديل الرصيد بنجاح.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
