<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PracticeController extends Controller
{
    public function index(): View
    {
        return view('practice-modular');
    }
}
