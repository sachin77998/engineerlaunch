<?php

namespace App\Models\Referral;

use Illuminate\Database\Eloquent\Model;

// Every reward credit and withdrawal debit; a user's balance is the sum of their rows.
class ReferralLedger extends Model
{
    protected $table = 'referral_ledger';

    protected $guarded = [];

    public static function balance(int $userId): int
    {
        return (int) static::where('user_id', $userId)->sum('amount');
    }
}
