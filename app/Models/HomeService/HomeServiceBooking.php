<?php

namespace App\Models\HomeService;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeServiceBooking extends Model
{
    protected $guarded = [];

    protected $casts = ['details' => 'array', 'slot_date' => 'date', 'has_quote_items' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(HomeServiceBookingItem::class); }
    public function review() { return $this->hasOne(HomeServiceReview::class); }

    public function reference(): string
    {
        return 'HS' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return config('home_services.statuses')[$this->status] ?? ucfirst($this->status);
    }

    public function categoryLabel(): string
    {
        return config('home_services.categories')[$this->category][0] ?? ucfirst($this->category);
    }

    public function slotLabel(): string
    {
        return $this->slot_date->format('D, d M') . ' · ' . (config('home_services.slots')[$this->slot] ?? $this->slot);
    }
}
