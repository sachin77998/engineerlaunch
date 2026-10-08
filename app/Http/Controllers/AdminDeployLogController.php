<?php

namespace App\Http\Controllers;

/** Owner-only view of the last cPanel deployment run (written by scripts/cpanel-deploy.sh). */
class AdminDeployLogController extends Controller
{
    public function __invoke()
    {
        $path = storage_path('logs/deploy.log');
        $text = is_file($path) ? file_get_contents($path) : 'No deployment log yet. It is written on the next cPanel deployment.';
        return response($text, 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-store']);
    }
}
