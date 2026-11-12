<?php

namespace App\Services\Property;

use App\Models\Property\Project;
use App\Models\Property\Property;
use App\Models\Property\SiteVisit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

// Bookable visit slots for a listing (owner's days and hours from the posting wizard) or a project (schedule set in admin),
// minus slots that are past, too soon, or already full.
class VisitSlots
{
    /** ['days' => ISO weekdays, 'start' => 'H:i', 'end' => 'H:i', 'capacity' => n] */
    public function schedule(Model $v): array
    {
        $c = config('site_visits');
        if ($v instanceof Project) {
            return ['days' => $v->visit_days ?: range(1, 7), 'start' => $v->visit_start ?: $c['project']['start'], 'end' => $v->visit_end ?: $c['project']['end'],
                'capacity' => $v->visit_capacity ?: $c['project']['capacity']];
        }
        $days = match ($v->availability) { 'weekday' => [1, 2, 3, 4, 5], 'weekend' => [6, 7], default => range(1, 7) };
        [$start, $end] = $v->visit_all_day || !$v->visit_start || !$v->visit_end ? $c['all_day'] : [$v->visit_start, $v->visit_end];
        return ['days' => $days, 'start' => $start, 'end' => $end, 'capacity' => $c['property_capacity']];
    }

    /** Days with their free slots: [['date' => 'Y-m-d', 'label' => 'Mon 16 Nov', 'slots' => [['at' => 'Y-m-d H:i', 'label' => '10:00 AM', 'left' => 2]]]] */
    public function available(Model $v, ?int $ignoreVisitId = null): array
    {
        $c = config('site_visits');
        $s = $this->schedule($v);
        $earliest = now()->addMinutes($c['lead_minutes']);
        $until = today()->addDays($c['days_ahead']);
        $taken = SiteVisit::open()->where(['visitable_type' => $v->getMorphClass(), 'visitable_id' => $v->getKey()])
            ->whereBetween('visit_at', [today(), $until->copy()->endOfDay()])->when($ignoreVisitId, fn ($q) => $q->whereKeyNot($ignoreVisitId))
            ->get(['visit_at'])->countBy(fn ($x) => $x->visit_at->format('Y-m-d H:i'));
        $out = [];
        foreach (CarbonPeriod::create(today(), $until) as $day) {
            if (!in_array($day->isoWeekday(), array_map('intval', $s['days']), true)) continue;
            $slots = [];
            $at = Carbon::parse($day->toDateString() . ' ' . $s['start']);
            $end = Carbon::parse($day->toDateString() . ' ' . $s['end']);
            for (; $at->copy()->addMinutes($c['slot_minutes'])->lte($end); $at->addMinutes($c['slot_minutes'])) {
                if ($at->lt($earliest)) continue;
                $left = $s['capacity'] - ($taken[$at->format('Y-m-d H:i')] ?? 0);
                if ($left > 0) $slots[] = ['at' => $at->format('Y-m-d H:i'), 'label' => $at->format('g:i A'), 'left' => $left];
            }
            if ($slots) $out[] = ['date' => $day->toDateString(), 'label' => $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D j M')), 'slots' => $slots];
        }
        return $out;
    }

    public function isFree(Model $v, Carbon $at, ?int $ignoreVisitId = null): bool
    {
        foreach ($this->available($v, $ignoreVisitId) as $day) {
            foreach ($day['slots'] as $slot) if ($slot['at'] === $at->format('Y-m-d H:i')) return true;
        }
        return false;
    }

    /** Who receives booking emails: the listing owner, or the project's sales / builder contact. */
    public function hostEmail(Model $v): ?string
    {
        if ($v instanceof Project) return $v->contact_email ?: $v->builder?->email;
        return $v instanceof Property ? ($v->owner?->email ?: $v->user?->email) : null;
    }
}
