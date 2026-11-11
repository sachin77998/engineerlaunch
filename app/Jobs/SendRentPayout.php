<?php

namespace App\Jobs;

use App\Models\RentTransfer;
use App\Services\Payments\RentPayouts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Sends a card-paid rent transfer to the payee. Safe to run twice: RentPayouts::send only acts on status "paid".
class SendRentPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $transferId) {}

    public function handle(RentPayouts $payouts): void
    {
        if ($t = RentTransfer::find($this->transferId)) $payouts->send($t);
    }
}
