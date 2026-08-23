<?php

namespace App\Http\Controllers;

use App\Services\PageDataService;
use Illuminate\View\View;

class AboutUsController extends Controller
{
    public function __construct(private PageDataService $pages)
    {
    }

    public function index(): View
    {
        return view('about-bootstrap', $this->pages->about());
    }
}
