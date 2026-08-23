<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ContactUsController extends Controller
{
    public function index(): View
    {
        return view('contact');
    }
}
