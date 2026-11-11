<?php

namespace App\Models\Referral;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReferralWithdrawal extends Model
{
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class); }

    public function statusLabel(): string
    {
        return config('referrals.withdrawal_statuses')[$this->status] ?? ucfirst($this->status);
    }
}
