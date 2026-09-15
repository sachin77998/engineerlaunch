<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class LearningController extends Controller
{
    public function index(): View
    {
        $tracks = collect(config('learning_tracks', []))
            ->sortBy(fn($track, $slug) => $slug === 'sql' ? 1 : 0)
            ->all();

        return view('learning.hub-modular', compact('tracks'));
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
            $topic = $tracks[$track]['topics'][$module];
            $lessonConfig = $topic['lessons_config'] ?? null;
            $lessons = $lessonConfig
                ? array_slice(config($lessonConfig, []), $topic['offset'] ?? 0, $topic['limit'] ?? null)
                : array_merge(config('laravel_foundations', []), config('laravel_advanced', []));

            return view($topic['view'] ?? ($lessonConfig ? 'learning.spring-tutorials' : 'learning.tutorials'), [
                'trackSlug' => $track,
                'track' => $tracks[$track],
                'moduleSlug' => $module,
                'module' => $topic,
                'lessons' => $lessons,
            ]);
        }

        if ($track === 'sql') {
            $questions = $this->paginateQuestions($tracks[$track]['topics'][$module]['questions']);
            return view('learning.sql-questions', [
                'trackSlug' => $track,
                'track' => $tracks[$track],
                'moduleSlug' => $module,
                'module' => $tracks[$track]['topics'][$module],
                'questions' => $questions,
                'allTracks' => $tracks,
            ]);
        }

        $questions = $this->paginateQuestions($tracks[$track]['topics'][$module]['questions']);
        return view('learning.questions-modular', [
            'trackSlug' => $track,
            'track' => $tracks[$track],
            'moduleSlug' => $module,
            'module' => $tracks[$track]['topics'][$module],
            'questions' => $questions,
            'allTracks' => $tracks,
        ]);
    }

    private function paginateQuestions(array $questions): LengthAwarePaginator
    {
        $page = max(1, (int) request()->query('page', 1));
        $perPage = 10;

        return new LengthAwarePaginator(
            array_slice($questions, ($page - 1) * $perPage, $perPage),
            count($questions),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
