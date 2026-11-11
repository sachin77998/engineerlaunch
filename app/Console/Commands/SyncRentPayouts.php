<?php

namespace App\Console\Commands;

use App\Jobs\SendRentPayout;
use App\Models\RentTransfer;
use App\Services\Payments\RentPayouts;
use Illuminate\Console\Command;

// Picks up card-paid transfers whose payout job never ran, and polls Cashfree for transfers still on their way.
class SyncRentPayouts extends Command
{
    protected $signature = 'pay-rent:sync';

    protected $description = 'Send stuck rent payouts and update pending transfer statuses from Cashfree';

    public function handle(RentPayouts $payouts): int
    {
        if (!$payouts->live()) {
            $this->info('Pay rent is not live; nothing to do.');
            return self::SUCCESS;
        }
        $stuck = RentTransfer::where('status', 'paid')->where('updated_at', '<', now()->subMinutes(5))->pluck('id');
        foreach ($stuck as $id) SendRentPayout::dispatch($id);
        $pending = RentTransfer::where('status', 'payout_pending')->where('updated_at', '<', now()->subMinutes(2))->limit(200)->get();
        foreach ($pending as $t) $payouts->sync($t);
        $this->info("Re-sent {$stuck->count()}, checked {$pending->count()}.");
        return self::SUCCESS;
    }
}
