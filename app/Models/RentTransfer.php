<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A card-funded payment to a landlord / tutor / society. The account number is stored encrypted; only the last 4 digits are shown.
class RentTransfer extends Model
{
    protected $guarded = [];

    protected $casts = ['details' => 'array', 'payout_response' => 'array', 'payee_account' => 'encrypted', 'paid_at' => 'datetime', 'completed_at' => 'datetime'];

    protected $hidden = ['payee_account'];

    public function user() { return $this->belongsTo(User::class); }
    public function payment() { return $this->belongsTo(Payment::class); }

    public function statusLabel(): string
    {
        return config('pay_rent.statuses')[$this->status] ?? ucfirst($this->status);
    }

    public function purposeLabel(): string
    {
        return config('pay_rent.purposes')[$this->purpose][0] ?? ucfirst($this->purpose);
    }

    public function payeeLabel(): string
    {
        return $this->method === 'upi' ? $this->payee_vpa : 'A/c ••••' . $this->payee_account_last4 . ' · ' . $this->payee_ifsc;
    }

    /** Fee and total for an amount, using the configured convenience fee plus GST on it. */
    public static function charges(int $amount): array
    {
        $fee = (int) ceil($amount * config('pay_rent.fee_pct') / 100 * (1 + config('pay_rent.gst_on_fee_pct') / 100));
        return ['fee' => $fee, 'total' => $amount + $fee];
    }
}
