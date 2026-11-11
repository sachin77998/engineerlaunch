<?php

namespace App\Http\Controllers;

use App\Models\Forum\ForumAnswer;
use App\Models\Forum\ForumAnswerVote;
use App\Models\Forum\ForumQuestion;
use App\Models\Forum\ForumReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Property forum: ask and answer questions by category, search, upvote and pick the best answer, top authors of the month.
class ForumController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $base = ForumQuestion::visible()->with(['user:id,name', 'topAnswer.user:id,name']);
        return view('forum.index', [
            'q' => $q,
            'results' => $q !== '' ? (clone $base)->where(fn ($w) => $w->where('title', 'like', '%' . $q . '%')->orWhere('body', 'like', '%' . $q . '%'))->latest()->paginate(config('forum.per_page'))->withQueryString() : null,
            'answered' => (clone $base)->where('answers_count', '>', 0)->orderByDesc('last_answered_at')->take(8)->get(),
            'unanswered' => (clone $base)->where('answers_count', 0)->latest()->take(8)->get(),
            'counts' => ForumQuestion::visible()->selectRaw('category, count(*) n')->groupBy('category')->pluck('n', 'category'),
        ]);
    }

    public function category(Request $request, string $category)
    {
        abort_unless(isset(config('forum.categories')[$category]), 404);
        $q = trim((string) $request->query('q'));
        $base = ForumQuestion::visible()->where('category', $category)->with(['user:id,name', 'topAnswer.user:id,name']);
        $answered = (clone $base)->where('answers_count', '>', 0)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('body', 'like', '%' . $q . '%')))
            ->orderByDesc('last_answered_at')->paginate(config('forum.per_page'))->withQueryString();
        return view('forum.category', [
            'category' => $category, 'cat' => config('forum.categories')[$category], 'q' => $q, 'answered' => $answered,
            'unanswered' => (clone $base)->where('answers_count', 0)->when($q !== '', fn ($w) => $w->where('title', 'like', '%' . $q . '%'))->latest()->take(10)->get(),
            'authors' => $this->topAuthors($category),
        ]);
    }

    public function show(Request $request, string $category, string $question)
    {
        $item = ForumQuestion::with('user:id,name')->findOrFail((int) $question);
        abort_if($item->is_hidden && !$this->isAdmin($request), 404);
        if ($item->category !== $category || $request->route('question') !== basename(parse_url($item->url(), PHP_URL_PATH))) return redirect($item->url(), 301);
        $seen = $request->session()->get('forum_seen', []);
        if (!in_array($item->id, $seen, true)) { $item->increment('views'); $request->session()->put('forum_seen', array_slice(array_merge($seen, [$item->id]), -200)); }
        $answers = $item->answers()->with('user:id,name')->when(!$this->isAdmin($request), fn ($q) => $q->where('is_hidden', false))
            ->orderByDesc('is_best')->orderByDesc('upvotes')->oldest()->get();
        return view('forum.show', [
            'item' => $item, 'answers' => $answers, 'cat' => config('forum.categories')[$item->category],
            'voted' => $request->user() ? ForumAnswerVote::where('user_id', $request->user()->id)->whereIn('forum_answer_id', $answers->pluck('id'))->pluck('forum_answer_id')->all() : [],
            'related' => ForumQuestion::visible()->where('category', $item->category)->where('id', '!=', $item->id)->where('answers_count', '>', 0)->orderByDesc('last_answered_at')->take(6)->get(),
        ]);
    }

    public function ask(Request $request)
    {
        $o = config('forum');
        $v = $request->validate([
            'category' => 'required|in:' . implode(',', array_keys($o['categories'])),
            'title' => 'required|string|min:' . $o['title_min'] . '|max:' . $o['title_max'],
            'body' => 'nullable|string|max:' . $o['body_max'], 'city' => 'nullable|string|max:80',
        ]);
        $uid = $request->user()->id;
        if (ForumQuestion::where('user_id', $uid)->where('created_at', '>=', now()->startOfDay())->count() >= $o['daily_questions']) return back()->withInput()->withErrors(['title' => 'You have reached today\'s limit of ' . $o['daily_questions'] . ' questions.']);
        $title = trim(preg_replace('/\s+/u', ' ', $v['title']));
        if ($dup = ForumQuestion::where('user_id', $uid)->where('title', $title)->first()) return redirect($dup->url())->with('success', 'You already asked this question.');
        $q = ForumQuestion::create(['user_id' => $uid, 'category' => $v['category'], 'title' => $title, 'slug' => ForumQuestion::slugFor($title), 'body' => $v['body'] ?? null, 'city' => $v['city'] ?? null]);
        return redirect($q->url())->with('success', 'Your question is posted. You will see answers here.');
    }

    public function answer(Request $request, ForumQuestion $question)
    {
        abort_if($question->is_hidden, 404);
        $o = config('forum');
        $v = $request->validate(['body' => 'required|string|min:' . $o['answer_min'] . '|max:' . $o['answer_max']]);
        $uid = $request->user()->id;
        if (ForumAnswer::where('user_id', $uid)->where('created_at', '>=', now()->startOfDay())->count() >= $o['daily_answers']) return back()->withInput()->withErrors(['body' => 'You have reached today\'s answer limit.']);
        $question->answers()->create(['user_id' => $uid, 'body' => trim($v['body'])]);
        $question->recountAnswers();
        return redirect($question->url() . '#answers')->with('success', 'Thanks for answering!');
    }

    public function vote(Request $request, ForumAnswer $answer)
    {
        abort_if($answer->is_hidden, 404);
        $uid = $request->user()->id;
        if ($answer->user_id === $uid) return back()->withErrors(['vote' => 'You cannot upvote your own answer.']);
        DB::transaction(function () use ($answer, $uid) {
            $existing = ForumAnswerVote::where('forum_answer_id', $answer->id)->where('user_id', $uid)->first();
            $existing ? $existing->delete() : ForumAnswerVote::create(['forum_answer_id' => $answer->id, 'user_id' => $uid]);
            $answer->update(['upvotes' => ForumAnswerVote::where('forum_answer_id', $answer->id)->count()]);
        });
        return redirect($answer->question->url() . '#a' . $answer->id);
    }

    /** The asker marks one answer as best (again to unmark). */
    public function best(Request $request, ForumAnswer $answer)
    {
        $q = $answer->question;
        abort_unless($q->user_id === $request->user()->id, 403);
        $makeBest = !$answer->is_best;
        $q->answers()->update(['is_best' => false]);
        if ($makeBest) $answer->update(['is_best' => true]);
        return redirect($q->url() . '#a' . $answer->id);
    }

    public function destroyAnswer(Request $request, ForumAnswer $answer)
    {
        abort_unless($answer->user_id === $request->user()->id, 403);
        $q = $answer->question;
        $answer->delete();
        $q->recountAnswers();
        return redirect($q->url() . '#answers')->with('success', 'Your answer was deleted.');
    }

    public function report(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['question', 'answer'], true), 404);
        $target = $type === 'question' ? ForumQuestion::findOrFail($id) : ForumAnswer::findOrFail($id);
        $v = $request->validate(['reason' => 'required|in:' . implode(',', array_keys(config('forum.report_reasons')))]);
        ForumReport::firstOrCreate(['target_type' => $type, 'target_id' => $id, 'user_id' => $request->user()->id], ['reason' => $v['reason']]);
        $open = ForumReport::where('target_type', $type)->where('target_id', $id)->whereNull('resolved_at')->count();
        if ($open >= config('forum.auto_hide_reports') && !$target->is_hidden) {
            $target->update(['is_hidden' => true]);
            if ($type === 'answer') $target->question->recountAnswers();
        }
        $url = $type === 'question' ? $target->url() : $target->question->url();
        return redirect($url)->with('success', 'Thanks — our team will review it.');
    }

    public function author(Request $request, User $user)
    {
        $answers = ForumAnswer::where('user_id', $user->id)->where('is_hidden', false)->whereHas('question', fn ($q) => $q->visible());
        return view('forum.author', [
            'author' => $user, 'stats' => ['answers' => (clone $answers)->count(), 'upvotes' => (int) (clone $answers)->sum('upvotes'), 'best' => (clone $answers)->where('is_best', true)->count(),
                'questions' => ForumQuestion::visible()->where('user_id', $user->id)->count()],
            'recent' => $answers->with('question')->latest()->paginate(20),
        ]);
    }

    /** Most answers this month in a category (or overall), with upvotes, best answers and their latest questions. */
    private function topAuthors(?string $category)
    {
        $rows = ForumAnswer::query()->join('forum_questions as q', 'q.id', '=', 'forum_answers.forum_question_id')
            ->where('forum_answers.is_hidden', false)->where('q.is_hidden', false)->where('forum_answers.created_at', '>=', now()->startOfMonth())
            ->when($category, fn ($w) => $w->where('q.category', $category))
            ->selectRaw('forum_answers.user_id, count(*) answers, sum(forum_answers.upvotes) upvotes, sum(forum_answers.is_best) best')
            ->groupBy('forum_answers.user_id')->orderByDesc('answers')->orderByDesc('upvotes')->take(6)->get();
        $users = User::whereIn('id', $rows->pluck('user_id'))->get(['id', 'name'])->keyBy('id');
        return $rows->map(fn ($r) => (object) [
            'user' => $users[$r->user_id] ?? null, 'answers' => (int) $r->answers, 'upvotes' => (int) $r->upvotes, 'best' => (int) $r->best,
            'questions' => ForumQuestion::visible()->whereHas('answers', fn ($a) => $a->where('user_id', $r->user_id)->where('is_hidden', false))
                ->when($category, fn ($w) => $w->where('category', $category))->orderByDesc('last_answered_at')->take(5)->get(['id', 'category', 'title', 'slug']),
        ])->filter(fn ($a) => $a->user)->values();
    }

    private function isAdmin(Request $request): bool
    {
        $u = $request->user();
        return $u && ($u->role === 'admin' || $u->role_code === 2) && strcasecmp($u->email, (string) config('owner.email')) === 0;
    }
}
