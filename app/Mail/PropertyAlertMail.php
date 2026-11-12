<?php

namespace App\Mail;

use App\Models\Property\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

// Plain-text email with new listings for a saved search, a link to all results and a one-click way to stop the alert.
class PropertyAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SavedSearch $search, public Collection $listings, public int $total)
    {
    }

    public function build(): self
    {
        $s = $this->search;
        $lines = $this->listings->map(fn ($p) => '• ' . $p->title . ' — ' . $p->headlinePrice() . ($p->locality ? ', ' . $p->locality : '') . "\n  " . route('properties.show', $p))->all();
        $body = implode("\n", array_merge(
            [($this->total === 1 ? '1 new listing matches' : $this->total . ' new listings match') . ' your saved search "' . $s->name . '":', ''],
            $lines,
            $this->total > $this->listings->count() ? ['', '…and ' . ($this->total - $this->listings->count()) . ' more.'] : [],
            ['', 'See all results: ' . $s->url(), 'Manage your alerts: ' . route('saved-searches.index'),
                'Stop this alert: ' . URL::signedRoute('saved-searches.unsubscribe', $s)]
        ));
        $subject = ($this->total === 1 ? 'New listing: ' . $this->listings->first()->title : $this->total . ' new listings for ' . $s->name);
        return $this->subject($subject)->text('emails.hiring-text', ['body' => e($body)]);
    }
}
