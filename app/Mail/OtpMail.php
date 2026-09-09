<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp, public string $mailSubject = '')
    {
    }

    public function build(): self
    {
        $subject = $this->mailSubject ?: config('app.name').' - Your Verification OTP';
        return $this->subject($subject)->view('emails.otp');
    }
}
