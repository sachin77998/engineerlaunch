<?php

namespace App\Http\Controllers;

use App\Models\{NewsArticle, NewsFetchLog, NewsSource};
use App\Services\News\SourceUrlGuard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNewsController extends Controller
{
    public function index(): View
    {
        return view(
            'admin.news',
            [
                'sources' => NewsSource::latest()->get(),
                'articles' => NewsArticle::with('category')->latest()->paginate(30),
                'logs' => NewsFetchLog::with('source')->latest()->limit(20)->get()
            ]
        );
    }
    public function source(Request $r, SourceUrlGuard $guard)
    {
        $d = $r->validate(['name' => 'required|string|max:150', 'website' => 'nullable|url|max:255', 'feed_url' => 'required|url|max:500']);
        $guard->assertAllowed($d['feed_url']);
        NewsSource::updateOrCreate(['feed_url' => $d['feed_url']], $d + ['source_type' => 'rss', 'is_active' => true]);
        return back()->with('success', 'Source saved.');
    }
    public function moderate(Request $r, NewsArticle $article)
    {
        $d = $r->validate(['action' => 'required|in:publish,reject']);
        $article->update(['is_published' => $d['action'] === 'publish', 'processing_status' => $d['action'] === 'publish' ? 'published' : 'rejected', 'published_at' => $d['action'] === 'publish' ? now() : null, 'moderated_at' => now(), 'moderated_by' => $r->user()->id]);
        return back()->with('success', 'Article moderated.');
    }
}
