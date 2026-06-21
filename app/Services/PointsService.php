<?php

namespace App\Services;

use App\Models\PointsBalance;
use App\Models\PointsTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PointsService
{
    public function credit(User $user, float $amount, string $reason, ?string $refType = null, ?int $refId = null): PointsTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الإضافة يجب أن يكون أكبر من صفر');
        }

        return DB::transaction(function () use ($user, $amount, $reason, $refType, $refId) {
            $balance = PointsBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
            );

            $before = $balance->balance;
            $after = $before + $amount;

            $balance->update([
                'balance' => $after,
                'total_earned' => $balance->total_earned + $amount,
            ]);

            return PointsTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    public function debit(User $user, float $amount, string $reason, ?string $refType = null, ?int $refId = null): PointsTransaction
    {
        if ($amount <= 0) {
            throw new RuntimeException('مبلغ الخصم يجب أن يكون أكبر من صفر');
        }

        return DB::transaction(function () use ($user, $amount, $reason, $refType, $refId) {
            $balance = PointsBalance::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
            );

            if ($balance->balance < $amount) {
                throw new RuntimeException('رصيد النقاط غير كافٍ');
            }

            $before = $balance->balance;
            $after = $before - $amount;

            $balance->update([
                'balance' => $after,
                'total_spent' => $balance->total_spent + $amount,
            ]);

            return PointsTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'reference_type' => $refType,
                'reference_id' => $refId,
            ]);
        });
    }

    public function balance(User $user): float
    {
        $balance = PointsBalance::where('user_id', $user->id)->first();

        return $balance ? (float) $balance->balance : 0.0;
    }

    public function hasSufficient(User $user, float $amount): bool
    {
        return $this->balance($user) >= $amount;
    }

    public function revertTransaction(Transaction $tx): void
    {
        DB::transaction(function () use ($tx) {
            if ($tx->type === Transaction::TYPE_POINTS_RECHARGE && $tx->points_amount > 0) {
                $this->debit(
                    $tx->user,
                    $tx->points_amount,
                    'revoked',
                    'transaction',
                    $tx->id
                );
            }
        });
    }
}
