<?php

namespace App\Jobs;

use App\Models\BatchGeneration;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMikrotikCardBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'cards';

    public int $timeout = 600;

    public function __construct(public int $batchId) {}

    public function handle(): void
    {
        $batch = BatchGeneration::find($this->batchId);
        if (! $batch || $batch->status === 'completed') {
            return;
        }

        $batch->update(['status' => 'processing']);

        $successCount = 0;
        for ($i = 0; $i < $batch->quantity; $i++) {
            $transaction = Transaction::create([
                'profile_id' => $batch->profile_id,
                'type' => Transaction::TYPE_CARD_PURCHASE,
                'status' => Transaction::STATUS_PENDING_MATCH,
            ]);

            GenerateMikrotikCardJob::dispatch(
                $transaction->id,
                $batch->generation_config
            )->onQueue('cards');

            $successCount++;
        }

        $batch->update([
            'generated_count' => $successCount,
            'status' => $successCount === $batch->quantity ? 'completed' : 'partially_completed',
        ]);
    }
}
