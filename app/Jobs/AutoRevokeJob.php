<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\MikroTikService;
use App\Services\PointsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RouterOS\Query;

class AutoRevokeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $transactionId)
    {
        $this->onQueue('cards');
    }

    public function handle(MikroTikService $mikroTik, PointsService $pointsService): void
    {
        $tx = Transaction::with('user')->find($this->transactionId);

        if (! $tx || $tx->verification_status !== Transaction::VERIFICATION_PENDING) {
            return;
        }

        DB::transaction(function () use ($tx, $mikroTik, $pointsService) {
            if ($tx->type === Transaction::TYPE_CARD_PURCHASE && $tx->mikrotik_username) {
                try {
                    $mikroTik->connect();
                    $removeQuery = (new Query('/tool/user-manager/user/remove'))
                        ->equal('numbers', $tx->mikrotik_username);
                    $mikroTik->getClient()->query($removeQuery)->read();
                } catch (\Throwable $e) {
                    Log::error('AutoRevoke: failed to remove user from MikroTik', [
                        'username' => $tx->mikrotik_username,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($tx->type === Transaction::TYPE_POINTS_RECHARGE && $tx->points_amount > 0) {
                try {
                    $pointsService->revertTransaction($tx);
                } catch (\Throwable $e) {
                    Log::error('AutoRevoke: failed to revert points', [
                        'transaction_id' => $tx->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $tx->update([
                'verification_status' => Transaction::VERIFICATION_REVOKED,
                'revoked_at' => now(),
            ]);

            if ($tx->user) {
                $tx->user->update([
                    'is_banned' => true,
                    'banned_at' => now(),
                    'ban_reason' => 'احتيال - تأكيد وهمي - لم يصل Webhook خلال 5 دقائق',
                ]);
            }
        });
    }
}
