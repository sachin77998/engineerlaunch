<?php

namespace App\Mail;

use App\Models\PortalEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PortalEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PortalEvent $event)
    {
    }

    public function build(): self
    {
        return $this->subject($this->event->title)->view('emails.portal-event');
    }
}
