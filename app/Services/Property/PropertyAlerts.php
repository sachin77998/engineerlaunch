<?php

namespace App\Services\Property;

use App\Mail\PropertyAlertMail;
use App\Models\Property\Property;
use App\Models\Property\SavedSearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

// Emails new listings to saved searches: instantly when a listing goes live, or as daily / weekly summaries.
// Each listing is sent at most once per saved search (property_alert_sends).
class PropertyAlerts
{
    public const PER_EMAIL = 10;

    /** A listing just went live: email every "instant" search it matches. Returns how many searches were alerted. */
    public function instant(Property $p): int
    {
        if ($p->status !== 'active') return 0;
        $modes = collect(config('properties.modes'))->filter(fn ($m) => $m['listing'] === $p->listing && $m['segment'] === $p->segment)->keys();
        $sent = 0;
        SavedSearch::with('user:id,email,name')->where('frequency', 'instant')->whereIn('mode', $modes)
            ->where(fn ($q) => $q->whereNull('city_slug')->orWhere('city_slug', $p->city_slug))
            ->chunkById(200, function ($searches) use ($p, &$sent) {
                foreach ($searches as $s) {
                    if (!$s->unsent($s->created_at)->whereKey($p->id)->exists()) continue;
                    $this->send($s, collect([$p]), 1);
                    $sent++;
                }
            });
        return $sent;
    }

    /** Daily or weekly summaries that are due. Returns how many emails were queued. */
    public function digest(string $frequency): int
    {
        $every = $frequency === 'weekly' ? now()->subDays(7)->addHour() : now()->subDay()->addHour();
        $sent = 0;
        SavedSearch::with('user:id,email,name')->where('frequency', $frequency)
            ->where(fn ($q) => $q->whereNull('last_sent_at')->orWhere('last_sent_at', '<=', $every))
            ->chunkById(200, function ($searches) use (&$sent, $frequency) {
                foreach ($searches as $s) {
                    // Never look back further than one period (plus a day) so a new alert doesn't email old stock.
                    $since = max($s->last_sent_at ?? $s->created_at, now()->subDays($frequency === 'weekly' ? 8 : 2));
                    $q = $s->unsent($since);
                    $total = (clone $q)->count();
                    if (!$total) { $s->forceFill(['last_sent_at' => now()])->save(); continue; }
                    $this->send($s, $q->with(['rentTerms', 'saleTerms', 'pgTerms'])->latest('published_at')->limit(self::PER_EMAIL)->get(), $total);
                    $sent++;
                }
            });
        return $sent;
    }

    private function send(SavedSearch $s, Collection $listings, int $total): void
    {
        $s->markSent($listings->pluck('id')->all());
        $s->forceFill(['last_sent_at' => now()])->save();
        if ($email = $s->user?->email) {
            rescue(fn () => Mail::to($email)->queue((new PropertyAlertMail($s, $listings, $total))->onQueue('emails')), null);
        }
    }
}
