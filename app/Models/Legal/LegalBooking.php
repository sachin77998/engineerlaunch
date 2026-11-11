<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LegalBooking extends Model
{
    protected $guarded = [];

    protected $casts = ['preferred_at' => 'datetime', 'has_quote_items' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(LegalBookingItem::class); }
    public function review() { return $this->hasOne(LegalBookingReview::class); }
    public function rentAgreement() { return $this->hasOne(RentAgreement::class); }

    public function reference(): string
    {
        return 'LS' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return config('legal.statuses')[$this->status] ?? ucfirst($this->status);
    }
}
