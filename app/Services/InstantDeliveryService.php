<?php

namespace App\Services;

use App\Events\CardPurchased;
use App\Events\PointsCredited;
use App\Jobs\AutoRevokeJob;
use App\Models\Profile;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InstantDeliveryService
{
    public function __construct(
        private PointsService $pointsService,
        private CardGeneratorService $cardGenerator,
        private MikroTikService $mikroTik
    ) {}

    public function process(array $data): array
    {
        $reference = $data['reference'];
        $lock = Cache::lock("verify_tx_{$reference}", 30);

        if (! $lock->get()) {
            throw new RuntimeException('هذه العملية قيد المعالجة حالياً.');
        }

        try {
            $existing = Transaction::where('jeeb_reference', $reference)
                ->where('type', ! empty($data['profile_id']) ? Transaction::TYPE_CARD_PURCHASE : Transaction::TYPE_POINTS_RECHARGE)
                ->first();

            if ($existing) {
                if ($existing->verification_status === Transaction::VERIFICATION_PENDING) {
                    return $this->buildResponse($existing);
                }
                throw new RuntimeException('رقم العملية مستخدم مسبقاً.');
            }

            $user = User::notBanned()->findOrFail($data['user_id']);

            if (! empty($data['profile_id'])) {
                return $this->processCardPurchase($user, $data);
            }

            return $this->processPointsRecharge($user, $data);
        } finally {
            $lock->release();
        }
    }

    private function processCardPurchase(User $user, array $data): array
    {
        $profile = Profile::active()->findOrFail($data['profile_id']);

        $tx = DB::transaction(function () use ($user, $profile, $data) {
            $tx = Transaction::create([
                'user_id' => $user->id,
                'profile_id' => $profile->id,
                'jeeb_reference' => $data['reference'],
                'webhook_amount' => $data['amount'],
                'webhook_phone' => $data['phone'] ?? null,
                'webhook_full_name' => $data['full_name'] ?? null,
                'type' => Transaction::TYPE_CARD_PURCHASE,
                'status' => Transaction::STATUS_PROCESSING,
                'verification_status' => Transaction::VERIFICATION_PENDING,
                'auto_revoke_at' => now()->addMinutes(5),
                'revoke_job_dispatched' => true,
            ]);

            $creds = $this->cardGenerator->generateCredentials([]);

            $this->mikroTik->connect();
            $this->mikroTik->createUserManagerUser(
                $creds['username'],
                $creds['password'],
                $profile->mikrotik_profile_name
            );

            $tx->update([
                'mikrotik_username' => $creds['username'],
                'mikrotik_password' => $creds['password'],
                'card_generated_at' => now(),
                'status' => Transaction::STATUS_COMPLETED,
            ]);

            AutoRevokeJob::dispatch($tx->id)->delay(now()->addMinutes(5));
            event(new CardPurchased($user, $tx));

            return $tx;
        });

        return $this->buildResponse($tx);
    }

    private function processPointsRecharge(User $user, array $data): array
    {
        $pointPrice = (float) (SystemSetting::getValue('point_price_yri', '10'));
        $points = (int) ($data['amount'] / $pointPrice);

        if ($points <= 0) {
            throw new RuntimeException('المبلغ غير كافٍ لشحن النقاط.');
        }

        $tx = DB::transaction(function () use ($user, $points, $data) {
            $balanceBefore = $this->pointsService->balance($user);

            $tx = Transaction::create([
                'user_id' => $user->id,
                'jeeb_reference' => $data['reference'],
                'webhook_amount' => $data['amount'],
                'webhook_phone' => $data['phone'] ?? null,
                'webhook_full_name' => $data['full_name'] ?? null,
                'type' => Transaction::TYPE_POINTS_RECHARGE,
                'status' => Transaction::STATUS_COMPLETED,
                'verification_status' => Transaction::VERIFICATION_PENDING,
                'points_amount' => $points,
                'points_before' => $balanceBefore,
                'points_after' => $balanceBefore + $points,
                'auto_revoke_at' => now()->addMinutes(5),
                'revoke_job_dispatched' => true,
            ]);

            $this->pointsService->credit($user, $points, 'points_recharge', 'verify', $tx->id);
            AutoRevokeJob::dispatch($tx->id)->delay(now()->addMinutes(5));
            event(new PointsCredited($user, $tx));

            return $tx;
        });

        return $this->buildResponse($tx);
    }

    private function buildResponse(Transaction $tx): array
    {
        $response = [
            'success' => true,
            'type' => $tx->type,
            'verification_status' => $tx->verification_status,
        ];

        if ($tx->type === Transaction::TYPE_CARD_PURCHASE) {
            $response['card'] = [
                'username' => $tx->mikrotik_username,
                'password' => $tx->mikrotik_password,
                'profile' => $tx->profile?->name,
            ];
        } else {
            $response['points'] = [
                'credited' => (float) $tx->points_amount,
                'balance' => (float) ($tx->points_after ?? 0),
                'amount_paid' => (float) $tx->webhook_amount,
                'point_price' => (float) SystemSetting::getValue('point_price_yri', '10'),
            ];
        }

        $response['message'] = $tx->type === Transaction::TYPE_CARD_PURCHASE
            ? 'تم توليد الكرت. سيتم تأكيد الدفع خلال 5 دقائق.'
            : 'تم إضافة النقاط. سيتم تأكيد الدفع خلال 5 دقائق.';

        return $response;
    }
}
