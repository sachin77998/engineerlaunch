<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LearningController extends Controller
{
    public function index(): View
    {
        return view('learning.hub-modular', ['tracks' => config('learning_tracks', [])]);
    }

    public function track(string $track): View
    {
        $tracks = config('learning_tracks', []);
        abort_unless(isset($tracks[$track]), 404);

        return view('learning.track-rich', ['slug' => $track, 'track' => $tracks[$track]]);
    }

    public function module(string $track, string $module): View
    {
        $tracks = config('learning_tracks', []);
        abort_unless(isset($tracks[$track]['topics'][$module]), 404);

        if (($tracks[$track]['topics'][$module]['type'] ?? null) === 'tutorial') {
            return view('learning.tutorials', [
                'trackSlug' => $track,
                'track' => $tracks[$track],
                'module' => $tracks[$track]['topics'][$module],
                'lessons' => config('laravel_foundations', []),
            ]);
        }

        return view('learning.questions-modular', [
            'trackSlug' => $track,
            'track' => $tracks[$track],
            'module' => $tracks[$track]['topics'][$module],
        ]);
    }
}
