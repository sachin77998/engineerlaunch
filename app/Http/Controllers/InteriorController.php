<?php

namespace App\Http\Controllers;

use App\Models\Interior\InteriorIdea;
use App\Models\Interior\InteriorLead;
use App\Models\Interior\InteriorProject;
use App\Services\Property\PropertyFormat as PF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

// Home interiors: landing, design idea gallery, projects, room-wise estimator, style quiz and free-quote requests.
class InteriorController extends Controller
{
    public function index()
    {
        $ideas = InteriorIdea::where('is_active', true);
        $count = fn ($col) => (clone $ideas)->whereNotNull($col)->selectRaw("$col as k, count(*) n, max(image_path) img")->groupBy($col)->get()->keyBy('k');
        return view('interiors.index', [
            'trending' => (clone $ideas)->where('is_trending', true)->orderBy('sort')->latest()->take(8)->get(),
            'byRoom' => $count('room'), 'byStyle' => $count('style'), 'byCategory' => $count('category'),
            'projects' => InteriorProject::where('is_active', true)->latest()->take(8)->get(),
            'featured' => InteriorProject::where('is_active', true)->where('is_featured', true)->latest()->first(),
            'hero' => (clone $ideas)->where('is_trending', true)->orderBy('sort')->first(),
        ]);
    }

    public function ideas(Request $request)
    {
        $c = config('interiors');
        $f = $request->validate(['room' => 'nullable|in:' . implode(',', array_keys($c['rooms'])), 'style' => 'nullable|in:' . implode(',', array_keys($c['styles'])), 'category' => 'nullable|in:' . implode(',', array_keys($c['categories']))]);
        $items = InteriorIdea::where('is_active', true)
            ->when($f['room'] ?? null, fn ($q, $v) => $q->where('room', $v))
            ->when($f['style'] ?? null, fn ($q, $v) => $q->where('style', $v))
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->orderByDesc('is_trending')->orderBy('sort')->latest()->paginate(24)->withQueryString();
        return view('interiors.ideas', ['items' => $items, 'f' => $f]);
    }

    public function idea(InteriorIdea $idea)
    {
        abort_unless($idea->is_active, 404);
        $more = InteriorIdea::where('is_active', true)->where('id', '!=', $idea->id)->where(fn ($q) => $q->where('room', $idea->room)->orWhere('style', $idea->style))->latest()->take(8)->get();
        return view('interiors.idea', ['idea' => $idea, 'more' => $more]);
    }

    public function project(InteriorProject $project)
    {
        abort_unless($project->is_active, 404);
        return view('interiors.project', ['p' => $project, 'more' => InteriorProject::where('is_active', true)->where('id', '!=', $project->id)->latest()->take(4)->get()]);
    }

    public function estimator(Request $request)
    {
        $bhk = (int) $request->query('bhk', 2);
        return view('interiors.estimator', ['bhk' => array_key_exists($bhk, config('interiors.bhk')) ? $bhk : 2]);
    }

    public function quiz()
    {
        return view('interiors.quiz');
    }

    public function quote(Request $request)
    {
        $c = config('interiors');
        $estimate = null;
        if ($request->filled('rooms') && is_array($request->query('rooms'))) {
            $estimate = InteriorLead::estimate($request->query('rooms'), array_key_exists($request->query('package'), $c['packages']) ? $request->query('package') : 'economy');
        }
        return view('interiors.quote', [
            'estimate' => $estimate, 'bhk' => (int) $request->query('bhk') ?: null, 'style' => array_key_exists($request->query('style'), $c['styles']) ? $request->query('style') : null,
            'idea' => $request->filled('idea') ? InteriorIdea::where('slug', $request->query('idea'))->where('is_active', true)->first() : null,
            'cities' => collect(config('properties.cities.India'))->sort()->values(),
        ]);
    }

    public function storeQuote(Request $request)
    {
        $c = config('interiors');
        $v = $request->validate([
            'name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^[6-9]\d{9}$/'], 'email' => 'nullable|email|max:191',
            'city' => 'required|string|max:80', 'bhk' => 'nullable|integer|in:' . implode(',', array_keys($c['bhk'])),
            'package' => 'nullable|in:' . implode(',', array_keys($c['packages'])), 'possession' => 'nullable|in:ready,within_3,later,renovation',
            'style' => 'nullable|in:' . implode(',', array_keys($c['styles'])), 'idea' => 'nullable|string|max:140',
            'rooms' => 'nullable|array', 'rooms.*' => 'nullable|integer|min:0|max:' . $c['max_rooms'], 'notes' => 'nullable|string|max:1000',
        ], ['phone.regex' => 'Enter a 10-digit mobile number.']);
        // The estimate is recomputed from room counts on the server, never taken from the browser.
        $estimate = !empty($v['rooms']) ? InteriorLead::estimate($v['rooms'], $v['package'] ?? 'economy') : null;
        $lead = InteriorLead::create([
            'user_id' => $request->user()?->id, 'name' => $v['name'], 'phone' => $v['phone'], 'email' => $v['email'] ?? $request->user()?->email, 'city' => $v['city'],
            'bhk' => $v['bhk'] ?? null, 'package' => $v['package'] ?? null, 'possession' => $v['possession'] ?? null, 'style' => $v['style'] ?? null,
            'estimate' => $estimate, 'estimate_total' => $estimate['total'] ?? null, 'notes' => $v['notes'] ?? null,
            'interior_idea_id' => !empty($v['idea']) ? InteriorIdea::where('slug', $v['idea'])->value('id') : null,
        ]);
        $this->notifyOwner($lead);
        return redirect()->route('interiors.index')->with('success', 'Thanks ' . $lead->name . ' — our interior designer will call you on ' . $lead->phone . ' to book a free consultation.');
    }

    private function notifyOwner(InteriorLead $l): void
    {
        if (!($to = config('owner.email'))) return;
        $c = config('interiors');
        $body = implode("\n", array_filter([
            'New interiors quote request', 'Name: ' . $l->name, 'Phone: +91 ' . $l->phone, $l->email ? 'Email: ' . $l->email : null, 'City: ' . $l->city,
            $l->bhk ? 'Home: ' . $c['bhk'][$l->bhk] : null, $l->package ? 'Package: ' . $c['packages'][$l->package][0] : null,
            $l->style ? 'Style: ' . $c['styles'][$l->style][0] : null, $l->estimate_total ? 'Estimate: ' . PF::full($l->estimate['low']) . ' – ' . PF::full($l->estimate['high']) : null,
            $l->idea ? 'Liked design: ' . $l->idea->title : null, $l->notes ? "\nNotes: " . $l->notes : null, '', route('admin.interiors.index'),
        ]));
        rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail('Interiors lead: ' . $l->name . ' (' . $l->city . ')', e($body), $l->email))->onQueue('emails')), null);
    }
}
