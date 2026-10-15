<?php

namespace App\Services\Trading;

use App\Models\Trading\TradingAuditLog;
use Illuminate\Support\Str;
use Throwable;

/** Writes the trading audit trail. Never pass secrets: OTPs, full PAN, Aadhaar or account numbers. */
class TradingAudit
{
    private const SECRET_KEYS = ['otp', 'pan', 'aadhaar', 'bank_account', 'password', 'token', 'id_number', 'new_pan', 'guardian_pan'];

    public function log(string $action, ?object $subject = null, array $meta = [], ?int $clientId = null): void
    {
        try {
            $request = request();
            TradingAuditLog::create([
                'user_id' => auth()->id(),
                'trading_client_id' => $clientId ?? ($subject->trading_client_id ?? null),
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject->id ?? null,
                'ip' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 250, ''),
                'request_id' => $request?->headers->get('X-Request-Id') ?? $request?->attributes->get('trading_request_id'),
                'meta' => $this->clean($meta) ?: null,
            ]);
        } catch (Throwable $e) {
            report($e);   // auditing must never break the action itself
        }
    }

    private function clean(array $meta): array
    {
        foreach ($meta as $key => $value) {
            if (in_array(strtolower((string) $key), self::SECRET_KEYS, true)) $meta[$key] = '[redacted]';
            elseif (is_array($value)) $meta[$key] = $this->clean($value);
        }
        return $meta;
    }
}
