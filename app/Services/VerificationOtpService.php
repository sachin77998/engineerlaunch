<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

final class VerificationOtpService
{
    public function usesFixedCode(): bool
    {
        // Never allow the public production application to use a predictable OTP,
        // even when VERIFICATION_OTP_DRIVER is accidentally misconfigured.
        return app()->environment(['local', 'testing']);
    }

    public function generate(): string
    {
        return $this->usesFixedCode()
            ? '123456'
            : (string) random_int(100000, 999999);
    }

    public function sendEmail(string $email, string $code, string $subject): void
    {
        if ($this->usesFixedCode()) {
            return;
        }

        Mail::raw(
            "Your Ascendia verification code is {$code}. It expires in 10 minutes. Do not share this code.",
            fn ($message) => $message->to($email)->subject($subject)
        );
    }
}
