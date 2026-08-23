<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('portal-v2', ['dsaTracks' => config('interview.dsa', [])]);
    }
}
