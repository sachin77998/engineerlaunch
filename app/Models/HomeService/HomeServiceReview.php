<?php

namespace App\Models\HomeService;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class HomeServiceReview extends Model
{
    protected $guarded = [];

    protected $casts = ['is_visible' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function booking() { return $this->belongsTo(HomeServiceBooking::class, 'home_service_booking_id'); }
}
