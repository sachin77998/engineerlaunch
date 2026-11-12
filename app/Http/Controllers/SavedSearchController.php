<?php

namespace App\Http\Controllers;

use App\Models\Property\SavedSearch;
use App\Services\Property\PropertySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// "My saved searches": save the filters of a search, choose how often to be emailed about new matches, open, edit or delete.
class SavedSearchController extends Controller
{
    public function index(Request $request)
    {
        $searches = SavedSearch::where('user_id', $request->user()->id)->latest('updated_at')->get();
        return view('properties.saved-searches', ['searches' => $searches, 'fresh' => $searches->mapWithKeys(fn ($s) => [$s->id => $s->newSinceSeen()])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(array_keys(config('properties.modes')))], 'city' => 'nullable|string|max:90|regex:/^[a-z0-9-]+$/',
            'query' => 'nullable|string|max:4000', 'name' => 'nullable|string|max:160', 'frequency' => ['required', Rule::in(array_keys(SavedSearch::FREQUENCIES))],
        ]);
        parse_str($data['query'] ?? '', $raw);
        // The saved filters go through the same rules as the search page, so an alert can never run a filter the page wouldn't.
        $filters = PropertySearch::clean(Validator::make($raw, PropertySearch::rules())->validate());
        $user = $request->user();
        $print = SavedSearch::fingerprint($data['mode'], $data['city'] ?? null, $filters);
        $existing = SavedSearch::where(['user_id' => $user->id, 'fingerprint' => $print])->first();
        if (!$existing && SavedSearch::where('user_id', $user->id)->count() >= SavedSearch::MAX_PER_USER) {
            return back()->with('error', 'You can keep up to ' . SavedSearch::MAX_PER_USER . ' saved searches. Delete one to save another.');
        }
        $cityName = $data['city'] ? (\App\Models\Property\PropertyCity::where('slug', $data['city'])->value('name') ?? ucwords(str_replace('-', ' ', $data['city']))) : null;
        SavedSearch::updateOrCreate(['user_id' => $user->id, 'fingerprint' => $print], [
            'name' => trim($data['name'] ?? '') ?: PropertySearch::label($data['mode'], $filters, $cityName),
            'mode' => $data['mode'], 'city_slug' => $data['city'] ?? null, 'filters' => $filters ?: null, 'frequency' => $data['frequency'],
            'seen_at' => now(),
        ]);
        return back()->with('success', $data['frequency'] === 'off' ? 'Search saved.' : 'Search saved — we will email you about new matching listings.');
    }

    public function update(Request $request, SavedSearch $search)
    {
        abort_unless($search->user_id === $request->user()->id, 404);
        $search->update($request->validate(['name' => 'required|string|max:160', 'frequency' => ['required', Rule::in(array_keys(SavedSearch::FREQUENCIES))]]));
        return back()->with('success', 'Saved search updated.');
    }

    public function destroy(Request $request, SavedSearch $search)
    {
        abort_unless($search->user_id === $request->user()->id, 404);
        $search->delete();
        return back()->with('success', 'Saved search deleted.');
    }

    /** Opens the results and resets the "new since you last looked" count. */
    public function open(Request $request, SavedSearch $search)
    {
        abort_unless($search->user_id === $request->user()->id, 404);
        $search->forceFill(['seen_at' => now()])->save();
        return redirect($search->url());
    }

    /** One-click "stop this alert" from the email (signed link, works without signing in). */
    public function unsubscribe(SavedSearch $search)
    {
        $search->update(['frequency' => 'off']);
        return view('properties.saved-search-off', ['search' => $search]);
    }
}
