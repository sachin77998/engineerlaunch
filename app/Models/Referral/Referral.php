<?php

namespace App\Models\Referral;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Referral extends Model
{
    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function ledger() { return $this->hasMany(ReferralLedger::class); }

    public function statusLabel(): string
    {
        return config('referrals.statuses')[$this->status] ?? ucfirst($this->status);
    }

    public function rejectLabel(): ?string
    {
        return config('referrals.reject_reasons')[$this->reject_reason] ?? null;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('referrals.photo', $this) : null;
    }

    public function deletePhoto(): void
    {
        if ($this->photo_path) Storage::disk('local')->delete($this->photo_path);
    }
}
