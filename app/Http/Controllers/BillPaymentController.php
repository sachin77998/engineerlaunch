<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Bills & Payments: categories (mobile, electricity, water, gas…) with their billers, and municipal services by city.
// The user picks a biller, enters the customer ID and continues to the biller's official payment page.
class BillPaymentController extends Controller
{
    private const ORDER = ['mobile', 'fastag', 'dth', 'electricity', 'loan', 'insurance', 'gas', 'cylinder', 'water', 'broadband', 'challan', 'creditcard', 'municipal'];

    public function index(Request $request)
    {
        $q = mb_strtolower(trim((string) $request->query('q')));
        $matches = $q === '' ? collect() : $this->billers()->filter(fn ($b) => str_contains(mb_strtolower($b['name'] . ' ' . $b['state']), $q))->take(40)->values();
        $cityMatches = $q === '' ? collect() : $this->cities()->filter(fn ($c) => str_contains(mb_strtolower($c['name'] . ' ' . $c['body']), $q))->values();
        return view('bills.index', ['categories' => $this->categories(), 'cities' => $this->cities(), 'q' => $q, 'matches' => $matches, 'cityMatches' => $cityMatches]);
    }

    public function category(Request $request, string $category)
    {
        $cat = $this->categories()[$category] ?? abort(404);
        $billers = collect($cat['billers']);
        $states = $billers->pluck('state')->unique()->sort()->values();
        $state = $states->contains($request->query('state')) ? $request->query('state') : null;
        $selected = $billers->firstWhere('slug', $request->query('biller')) ?? ($state ? $billers->firstWhere('state', $state) : $billers->first());
        return view('bills.category', ['categories' => $this->categories(), 'key' => $category, 'cat' => $cat,
            'billers' => $state ? $billers->where('state', $state)->values() : $billers, 'states' => $states, 'state' => $state, 'selected' => $selected]);
    }

    public function city(string $slug)
    {
        $city = $this->cities()->firstWhere('slug', $slug) ?? abort(404);
        return view('bills.city', ['categories' => $this->categories(), 'city' => $city,
            'services' => config('bill_payments.state_services.' . $city['state'], []),
            'others' => $this->cities()->where('state', $city['state'])->where('slug', '!=', $slug)->values()]);
    }

    private function categories(): array
    {
        return collect(config('bill_payments.categories'))->map(fn ($c, $key) => array_merge($c, ['key' => $key,
            'billers' => collect($c['billers'])->map(fn ($b) => ['name' => $b[0], 'state' => $b[1], 'url' => $b[2], 'slug' => Str::slug($b[0]),
                'id' => $b[3] ?? $c['id'], 'category' => $key])->all()]))
            // Same order as bill-pay apps; anything not listed follows.
            ->sortBy(fn ($c, $k) => ($i = array_search($k, self::ORDER, true)) === false ? 99 : $i)->all();
    }

    private function billers()
    {
        return collect($this->categories())->flatMap(fn ($c) => $c['billers']);
    }

    private function cities()
    {
        return collect(config('bill_payments.cities'))->map(fn ($c) => ['name' => $c[0], 'state' => $c[1], 'body' => $c[2], 'url' => $c[3], 'slug' => Str::slug($c[0])]);
    }
}
