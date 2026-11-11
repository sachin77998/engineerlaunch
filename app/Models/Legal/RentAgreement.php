<?php

namespace App\Models\Legal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RentAgreement extends Model
{
    protected $guarded = [];

    protected $casts = ['landlord' => 'array', 'tenant' => 'array', 'property' => 'array', 'terms' => 'array', 'addons' => 'array', 'steps_done' => 'array', 'start_date' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function booking() { return $this->belongsTo(LegalBooking::class, 'legal_booking_id'); }

    public function markDone(string $step): void
    {
        $this->steps_done = array_values(array_unique([...($this->steps_done ?? []), $step]));
    }

    public function isDone(string $step): bool
    {
        return in_array($step, $this->steps_done ?? [], true);
    }

    public function term(string $key, $default = null)
    {
        return ($this->terms ?? [])[$key] ?? $default;
    }

    public function endDate()
    {
        return $this->start_date?->copy()->addMonthsNoOverflow($this->months)->subDay();
    }

    public function reference(): string
    {
        return 'RA' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Uses an uploaded draft instead of our template. */
    public function usesDraft(): bool
    {
        return (bool) $this->draft_path && ($this->terms['use_draft'] ?? false);
    }
}
