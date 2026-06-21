<?php

namespace App\Listeners;

use App\Events\CardPurchased;
use App\Events\PointsCredited;
use App\Events\PointsSpent;
use App\Models\Challenge;
use App\Models\UserChallenge;
use App\Services\PointsService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckChallenges
{
    public function __construct(private PointsService $pointsService) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CardPurchased::class, [$this, 'handleCardPurchased']);
        $events->listen(PointsCredited::class, [$this, 'handlePointsEvent']);
        $events->listen(PointsSpent::class, [$this, 'handlePointsEvent']);
    }

    public function handleCardPurchased(CardPurchased $event): void
    {
        $this->evaluate($event->user, 'purchase_count', $event);
        $this->evaluate($event->user, 'profile_purchase', $event);
    }

    public function handlePointsEvent(PointsCredited|PointsSpent $event): void
    {
        $this->evaluate($event->user, 'points_spent_total', $event);
        $this->evaluate($event->user, 'points_spent_single', $event);
    }

    private function evaluate($user, string $eventType, $event): void
    {
        $challenges = Challenge::active()
            ->whereHas('conditions', fn ($q) => $q->where('condition_type', $eventType))
            ->get();

        foreach ($challenges as $challenge) {
            DB::transaction(function () use ($challenge, $user, $eventType) {
                $progress = UserChallenge::firstOrCreate([
                    'user_id' => $user->id,
                    'challenge_id' => $challenge->id,
                ], ['progress_data' => []]);

                if ($progress->completed_at && ! $progress->reward_claimed_at) {
                    return;
                }

                if ($challenge->max_completions > 0
                    && $progress->completion_count >= $challenge->max_completions) {
                    return;
                }

                $data = $progress->progress_data ?? [];
                $data[$eventType] = ($data[$eventType] ?? 0) + 1;
                $progress->progress_data = $data;

                $allMet = $challenge->conditions->every(function ($condition) use ($data) {
                    $current = $data[$condition->condition_type] ?? 0;
                    $target = $condition->value['min'] ?? $condition->value['count'] ?? 1;

                    return $current >= $target;
                });

                if ($allMet) {
                    $progress->completed_at = now();
                    $progress->completion_count += 1;
                    $this->awardRewards($challenge, $user);
                }

                $progress->save();
            });
        }
    }

    private function awardRewards(Challenge $challenge, $user): void
    {
        foreach ($challenge->rewards as $reward) {
            try {
                match ($reward->reward_type) {
                    'points' => $this->pointsService->credit(
                        $user,
                        $reward->value['points'] ?? 0,
                        'challenge_reward',
                        'challenge',
                        $challenge->id
                    ),
                    default => Log::info('Unknown challenge reward type', [
                        'type' => $reward->reward_type,
                    ]),
                };
            } catch (\Throwable $e) {
                Log::error('Challenge reward failed', [
                    'challenge_id' => $challenge->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
