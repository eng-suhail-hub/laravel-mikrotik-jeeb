<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\VoucherTheme;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        $themes = VoucherTheme::all();
        $recentCards = Transaction::whereNotNull('mikrotik_username')
            ->whereNull('raw_webhook_id')
            ->with('profile')
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.vouchers.index', compact('themes', 'recentCards'));
    }

    public function preview(Request $request)
    {
        $theme = VoucherTheme::findOrFail($request->theme_id);
        $cards = Transaction::whereIn('id', $request->transaction_ids ?? [])
            ->whereNotNull('mikrotik_username')
            ->get()->map(fn ($t) => [
                'username' => $t->mikrotik_username,
                'profile' => $t->profile?->name,
            ]);

        return view($theme->blade_view, compact('cards'));
    }

    public function print(Request $request)
    {
        $theme = VoucherTheme::findOrFail($request->theme_id);
        $cards = Transaction::whereIn('id', $request->transaction_ids ?? [])
            ->whereNotNull('mikrotik_username')
            ->get()->map(fn ($t) => [
                'username' => $t->mikrotik_username,
                'profile' => $t->profile?->name,
            ]);

        return view($theme->blade_view, compact('cards'));
    }
}
