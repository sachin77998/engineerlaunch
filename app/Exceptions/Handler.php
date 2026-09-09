<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
   
    protected $levels = [];
    protected $dontReport = [];
    protected $dontFlash = ['current_password','password','password_confirmation',];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
        });

        $this->renderable(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withInput($request->except(['password', 'password_confirmation', 'code']))
                ->withErrors(['rate_limit' => 'Too many attempts. Please wait a minute, then try again.']);
        });
    }
}
