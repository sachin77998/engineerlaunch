<?php

namespace App\Http\Controllers;

use App\Models\HomeService\HomeService;
use App\Models\HomeService\HomeServiceBooking;
use App\Models\HomeService\HomeServiceReview;
use App\Services\Property\PropertyFormat as PF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// Home services: pick a city, search services, book a category (quantities + date / time slot + address), packers & movers quotes, My bookings.
class HomeServiceController extends Controller
{
    public function index(Request $request)
    {
        if ($city = $request->session()->get('home_services_city')) return redirect()->route('home-services.city', Str::slug($city));
        return view('home-services.index', ['cities' => $this->cities(), 'city' => null, 'services' => collect(), 'starting' => collect(), 'ratings' => collect(), 'reviews' => collect()]);
    }

    public function city(Request $request, string $city)
    {
        $name = $this->cityName($city);
        $request->session()->put('home_services_city', $name);
        $services = HomeService::forCity($name);
        $visible = HomeServiceReview::where('is_visible', true);
        return view('home-services.index', [
            'cities' => $this->cities(), 'city' => $name, 'citySlug' => $city, 'services' => $services->groupBy('category'),
            'starting' => $services->whereNotNull('price')->groupBy('category')->map(fn ($g) => $g->min('price')),
            'ratings' => (clone $visible)->selectRaw('category, avg(rating) r, count(*) n')->groupBy('category')->get()->keyBy('category'),
            'reviews' => $visible->with('user:id,name')->latest()->take(6)->get(),
        ]);
    }

    public function category(Request $request, string $city, string $category)
    {
        $name = $this->cityName($city);
        abort_unless(isset(config('home_services.categories')[$category]), 404);
        $services = HomeService::forCity($name)->where('category', $category)->values();
        abort_if($category !== 'packers' && $services->isEmpty(), 404);
        return view('home-services.book', ['city' => $name, 'citySlug' => $city, 'category' => $category, 'services' => $services, 'days' => $this->days(), 'cities' => $this->cities(),
            'preset' => (int) $request->query('add'), 'moveType' => array_key_exists($request->query('type'), config('home_services.move.types')) ? $request->query('type') : null]);
    }

    public function store(Request $request, string $city, string $category)
    {
        $name = $this->cityName($city);
        $c = config('home_services');
        abort_unless(isset($c['categories'][$category]), 404);
        $rules = [
            'name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'address' => 'required|string|max:300', 'pincode' => 'nullable|digits:6',
            'slot_date' => 'required|date_format:Y-m-d', 'slot' => 'required|in:' . implode(',', array_keys($c['slots'])), 'notes' => 'nullable|string|max:500',
        ];
        $rules += $category === 'packers'
            ? ['move.type' => 'required|in:' . implode(',', array_keys($c['move']['types'])), 'move.size' => 'required|in:' . implode(',', array_keys($c['move']['sizes'])),
               'move.from' => 'required|string|max:200', 'move.to' => 'required|string|max:200', 'move.to_city' => 'nullable|string|max:80',
               'move.from_floor' => 'nullable|integer|min:0|max:60', 'move.to_floor' => 'nullable|integer|min:0|max:60', 'move.lift' => 'nullable|boolean',
               'move.extras' => 'nullable|array', 'move.extras.*' => 'in:' . implode(',', array_keys($c['move']['extras']))]
            : ['qty' => 'required|array', 'qty.*' => 'nullable|integer|min:0|max:' . $c['max_qty']];
        $v = $request->validate($rules, ['phone.regex' => 'Enter a 10-digit mobile number.']);
        if (!$this->slotOpen($v['slot_date'], $v['slot'])) return back()->withInput()->withErrors(['slot' => 'That time slot is no longer available — pick a later one.']);

        // Quantities are matched against this city's live services; prices always come from the database.
        $services = HomeService::forCity($name)->where('category', $category)->keyBy('id');
        $lines = $category === 'packers' ? collect() : collect($v['qty'])->map(fn ($q, $id) => [(int) $q, $services[$id] ?? null])->filter(fn ($x) => $x[0] > 0 && $x[1]);
        if ($category !== 'packers' && $lines->isEmpty()) return back()->withInput()->withErrors(['qty' => 'Choose at least one service.']);

        $booking = DB::transaction(function () use ($request, $v, $name, $category, $lines) {
            $b = HomeServiceBooking::create([
                'user_id' => $request->user()->id, 'category' => $category, 'city' => $name, 'name' => $v['name'], 'phone' => $v['phone'], 'address' => $v['address'],
                'pincode' => $v['pincode'] ?? null, 'slot_date' => $v['slot_date'], 'slot' => $v['slot'], 'notes' => $v['notes'] ?? null,
                'details' => $category === 'packers' ? array_merge($v['move'], ['lift' => $request->boolean('move.lift')]) : null,
                'total' => (int) $lines->sum(fn ($x) => ($x[1]->price ?? 0) * $x[0]),
                'has_quote_items' => $category === 'packers' || $lines->contains(fn ($x) => !$x[1]->price),
            ]);
            foreach ($lines as [$q, $s]) $b->items()->create(['home_service_id' => $s->id, 'name' => $s->name, 'qty' => $q, 'price' => $s->price, 'unit' => $s->unit]);
            return $b;
        });
        $this->notifyOwner($booking->load('items'));
        return redirect()->route('home-services.bookings')->with('success', 'Booked! ' . $booking->reference() . ' for ' . $booking->slotLabel() . '. We will confirm on ' . $booking->phone . '.');
    }

    public function bookings(Request $request)
    {
        return view('home-services.bookings', ['items' => HomeServiceBooking::with(['items', 'review'])->where('user_id', $request->user()->id)->latest()->paginate(20)]);
    }

    /** Rating and review for a completed booking (one per booking, editable). */
    public function review(Request $request, HomeServiceBooking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'completed', 422, 'You can review a booking once it is completed.');
        $v = $request->validate(['rating' => 'required|integer|min:1|max:5', 'body' => 'required|string|min:15|max:1500']);
        HomeServiceReview::updateOrCreate(['home_service_booking_id' => $booking->id], $v + ['user_id' => $booking->user_id, 'category' => $booking->category, 'city' => $booking->city]);
        return back()->with('success', 'Thanks for the review!');
    }

    public function cancel(Request $request, HomeServiceBooking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        if (in_array($booking->status, ['requested', 'confirmed'], true)) $booking->update(['status' => 'cancelled']);
        return back()->with('success', $booking->reference() . ' cancelled.');
    }

    /** Next N days with each slot marked open / closed (closed when it starts within the lead time). */
    private function days(): array
    {
        $c = config('home_services');
        return collect(range(0, $c['days_ahead'] - 1))->map(function ($i) use ($c) {
            $d = now()->startOfDay()->addDays($i);
            return ['date' => $d->toDateString(), 'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $d->format('D')), 'day' => $d->format('d M'),
                'slots' => collect($c['slots'])->map(fn ($l, $k) => ['key' => $k, 'label' => $l, 'open' => $this->slotOpen($d->toDateString(), $k)])->values()->all()];
        })->filter(fn ($d) => collect($d['slots'])->contains('open', true))->values()->all();
    }

    private function slotOpen(string $date, string $slot): bool
    {
        $c = config('home_services');
        $start = Carbon::parse($date . ' ' . substr($slot, 0, 2) . ':00');
        return $start->gte(now()->addHours($c['lead_hours'])) && $start->lte(now()->addDays($c['days_ahead']));
    }

    private function cities()
    {
        return collect(config('properties.cities.India'));
    }

    private function cityName(string $slug): string
    {
        $name = $this->cities()->first(fn ($c) => Str::slug($c) === $slug);
        abort_unless($name, 404);
        return $name;
    }

    private function notifyOwner(HomeServiceBooking $b): void
    {
        if (!($to = config('owner.email'))) return;
        $m = config('home_services.move');
        $d = $b->details ?? [];
        $body = implode("\n", array_filter([
            'New home services booking ' . $b->reference() . ' — ' . $b->categoryLabel(),
            'Name: ' . $b->name, 'Phone: +91 ' . $b->phone, 'City: ' . $b->city, 'Address: ' . $b->address . ($b->pincode ? ' - ' . $b->pincode : ''), 'Slot: ' . $b->slotLabel(), '',
            ...$b->items->map(fn ($i) => '• ' . $i->name . ' × ' . $i->qty . ' — ' . ($i->price ? PF::full($i->price * $i->qty) : 'price after inspection'))->all(),
            $d ? 'Move: ' . ($m['types'][$d['type']] ?? '') . ', ' . ($m['sizes'][$d['size']] ?? '') . "\nFrom: " . $d['from'] . "\nTo: " . $d['to'] . (!empty($d['to_city']) ? ', ' . $d['to_city'] : '') : null,
            $b->items->isNotEmpty() ? 'Total: ' . PF::full($b->total) . ($b->has_quote_items ? ' + items priced after inspection' : '') : null,
            $b->notes ? "\nNotes: " . $b->notes : null, '', route('admin.home-services.index'),
        ], fn ($v) => $v !== null));
        rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail('Home services ' . $b->reference() . ': ' . $b->categoryLabel(), e($body)))->onQueue('emails')), null);
    }
}
