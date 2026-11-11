<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// One review per completed booking, written by the customer who booked.
class LegalBookingReview extends Model
{
    protected $guarded = [];

    protected $casts = ['is_visible' => 'boolean'];

    public function booking() { return $this->belongsTo(LegalBooking::class, 'legal_booking_id'); }
    public function user() { return $this->belongsTo(User::class); }

    /** Visible reviews with the average, for the landing page. */
    public static function summary(): ?array
    {
        $q = static::where('is_visible', true);
        $count = (clone $q)->count();
        if (!$count) return null;
        return ['count' => $count, 'average' => round((float) (clone $q)->avg('rating'), 1),
            'latest' => $q->with(['user:id,name', 'booking.items'])->latest()->take(9)->get()];
    }
}
