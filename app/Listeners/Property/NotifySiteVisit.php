<?php

namespace App\Listeners\Property;

use App\Events\Property\SiteVisitChanged;
use App\Mail\HiringMail;
use App\Services\Property\VisitSlots;
use Illuminate\Support\Facades\Mail;

// Emails the other side of a site visit: the host when the visitor books, moves or cancels; the visitor when the host responds.
class NotifySiteVisit
{
    public function __construct(private VisitSlots $slots) {}

    public function handle(SiteVisitChanged $e): void
    {
        $v = $e->visit->loadMissing(['visitable', 'user:id,email,name']);
        $when = $v->visit_at->format('D, d M Y \a\t g:i A');
        $what = [
            'booked' => "New site visit request for {$v->title()} on $when.",
            'rescheduled' => "The site visit to {$v->title()} was moved to $when.",
            'cancelled' => "The site visit to {$v->title()} on $when was cancelled.",
            'confirmed' => "Your site visit to {$v->title()} on $when is confirmed.",
            'completed' => "Thanks for visiting {$v->title()}.",
            'no_show' => "Your site visit to {$v->title()} on $when was marked as missed.",
        ][$e->change] ?? null;
        if (!$what) return;
        $toHost = $e->by === 'visitor';
        $to = $toHost ? $this->slots->hostEmail($v->visitable) : ($v->email ?: $v->user?->email);
        if (!$to) return;
        $body = implode("\n", array_filter([
            $what, '', 'Reference: ' . $v->reference(),
            $toHost ? 'Visitor: ' . $v->name . ' · ' . $v->phone . ($v->email ? ' · ' . $v->email : '') : null,
            $toHost ? 'People: ' . $v->people : null,
            $v->notes && $toHost ? 'Notes: ' . $v->notes : null,
            !$toHost && $v->host_note ? 'Message: ' . $v->host_note : null,
            !$toHost && $v->place() ? 'Address: ' . $v->place() : null,
            '', $toHost ? 'Manage visits: ' . ($v->visitable_type === 'project' ? route('admin.projects.edit', $v->visitable_id) : route('site-visits.hosting')) : 'Your visits: ' . route('site-visits.index'),
        ], fn ($l) => $l !== null));
        rescue(fn () => Mail::to($to)->queue((new HiringMail('Site visit ' . $v->reference() . ': ' . $v->title(), e($body)))->onQueue('emails')), null);
    }
}
