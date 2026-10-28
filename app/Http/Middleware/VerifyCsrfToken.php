<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Zerodha order postbacks (verified by checksum in MarketController::postback).
        'trading/broker/kite/postback',
        // Paytm payment result (verified by CHECKSUMHASH and the Transaction Status API).
        'payments/paytm/callback',
    ];
}
