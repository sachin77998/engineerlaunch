<?php

namespace App\Mail;

use App\Models\Property\Property;
use App\Models\Property\PropertyLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Plain-text email to a listing's owner when someone sends an enquiry or asks for a visit.
class PropertyEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Property $property, public PropertyLead $lead)
    {
    }

    public function build(): self
    {
        $l = $this->lead;
        $body = implode("\n", array_filter([
            ($l->kind === 'visit' ? 'Site visit request' : 'New enquiry') . ' for: ' . $this->property->title,
            route('properties.show', $this->property),
            '',
            'From: ' . $l->name . ' (' . ($l->sender_type === 'dealer' ? 'dealer' : 'individual') . ')',
            'Phone: ' . $l->phone,
            $l->email ? 'Email: ' . $l->email : null,
            $l->visit_at ? 'Visit: ' . $l->visit_at->format('d M Y, g:i A') : null,
            $l->message ? "\nMessage:\n" . $l->message : null,
            '',
            'All enquiries: ' . route('properties.mine'),
        ], fn ($v) => $v !== null));
        $mail = $this->subject(($l->kind === 'visit' ? 'Visit request: ' : 'Enquiry: ') . $this->property->title)->text('emails.hiring-text', ['body' => e($body)]);
        if ($l->email) $mail->replyTo($l->email, $l->name);
        return $mail;
    }
}
