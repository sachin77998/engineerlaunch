<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Plain-text hiring emails (invites, candidate messages, offers). Optional files come from local storage paths.
class HiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $mailSubject, public string $body, public ?string $replyToEmail = null, public array $files = [], public ?int $messageId = null)
    {
    }

    public function build(): self
    {
        $mail = $this->subject($this->mailSubject)->text('emails.hiring-text', ['body' => $this->body]);
        if ($this->replyToEmail) $mail->replyTo($this->replyToEmail);
        foreach ($this->files as $name => $path) if (is_file($path)) $mail->attach($path, ['as' => $name]);
        return $mail;
    }
}
