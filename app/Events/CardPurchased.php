<?php

namespace App\Events;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CardPurchased
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Transaction $transaction
    ) {}
}
