<?php

namespace App\Http\Controllers;

use App\Models\Legal\LegalBooking;
use App\Models\Legal\LegalBookingReview;
use App\Models\Legal\LegalPartner;
use App\Models\Legal\LegalService;
use App\Services\Property\PropertyFormat as PF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// Property legal services: browse by audience and category per city, cart, booking and My bookings. The site owner fulfils bookings.
class LegalServiceController extends Controller
{
    public function index(Request $request, ?string $city = null)
    {
        if (!$city) return redirect()->route('legal.index', Str::slug($request->session()->get('legal_city', config('legal.default_city'))));
        $cityName = $this->cityName($city);
        $request->session()->put('legal_city', $cityName);
        $services = LegalService::forCity($cityName);
        $for = array_key_exists($request->query('for'), config('legal.audiences')) ? $request->query('for') : 'buyer';
        return view('legal.index', [
            'city' => $cityName, 'citySlug' => $city, 'services' => $services, 'for' => $for,
            'cities' => collect(config('properties.cities.India'))->sort()->values(), 'cart' => $this->cartIds($request),
            'partners' => LegalPartner::forCity($cityName), 'reviews' => LegalBookingReview::summary(),
            'completed' => LegalBooking::where('status', 'completed')->count(),
        ]);
    }

    public function show(Request $request, string $city, LegalService $service)
    {
        $cityName = $this->cityName($city);
        abort_unless($service->availableIn($cityName), 404);
        $related = LegalService::forCity($cityName)->where('category', $service->category)->where('id', '!=', $service->id)->take(4);
        return view('legal.show', ['service' => $service, 'city' => $cityName, 'citySlug' => $city, 'related' => $related, 'cart' => $this->cartIds($request)]);
    }

    public function add(Request $request, LegalService $service)
    {
        abort_unless($service->is_active, 404);
        $ids = $this->cartIds($request);
        if (!in_array($service->id, $ids, true) && count($ids) < config('legal.cart_max')) $ids[] = $service->id;
        $request->session()->put('legal_cart', $ids);
        if ($request->input('then') === 'cart') return redirect()->route('legal.cart');
        return back()->with('success', $service->name . ' added to your cart.');
    }

    public function remove(Request $request, LegalService $service)
    {
        $request->session()->put('legal_cart', array_values(array_diff($this->cartIds($request), [$service->id])));
        return back();
    }

    public function cart(Request $request)
    {
        $ids = $this->cartIds($request);
        $city = $request->session()->get('legal_city', config('legal.default_city'));
        return view('legal.cart', [
            'items' => LegalService::whereIn('id', $ids)->where('is_active', true)->get(), 'city' => $city, 'citySlug' => Str::slug($city),
            'cities' => collect(config('properties.cities.India'))->sort()->values(), 'cart' => $ids,
        ]);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'email' => 'nullable|email|max:191',
            'city' => 'required|string|max:80',
            'address' => 'nullable|string|max:255',
            'preferred_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:1000',
        ], ['phone.regex' => 'Enter a 10-digit mobile number.']);
        $items = LegalService::whereIn('id', $this->cartIds($request))->where('is_active', true)->get();
        if ($items->isEmpty()) return redirect()->route('legal.cart')->with('error', 'Your cart is empty.');
        $booking = DB::transaction(function () use ($request, $data, $items) {
            $b = LegalBooking::create($data + ['user_id' => $request->user()->id, 'total' => $items->sum('price'), 'has_quote_items' => $items->contains(fn ($s) => !$s->price)]);
            foreach ($items as $s) $b->items()->create(['legal_service_id' => $s->id, 'name' => $s->name, 'price' => $s->price]);
            return $b;
        });
        $request->session()->forget('legal_cart');
        $this->notifyOwner($booking->load('items'));
        return redirect()->route('legal.bookings')->with('success', 'Booking ' . $booking->reference() . ' placed. Our legal team will call you on ' . $booking->phone . ' to confirm.');
    }

    public function bookings(Request $request)
    {
        return view('legal.bookings', [
            'items' => LegalBooking::with(['items', 'review', 'rentAgreement'])->where('user_id', $request->user()->id)->latest()->get(),
            'cart' => $this->cartIds($request), 'city' => $request->session()->get('legal_city', config('legal.default_city')),
        ]);
    }

    public function cancel(Request $request, LegalBooking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        if ($booking->status === 'requested') $booking->update(['status' => 'cancelled']);
        return back()->with('success', 'Booking ' . $booking->reference() . ' cancelled.');
    }

    /** Customer review of a completed booking (one per booking, editable). */
    public function review(Request $request, LegalBooking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        abort_unless($booking->status === 'completed', 422, 'You can review a booking once it is completed.');
        $v = $request->validate(['rating' => 'required|integer|min:1|max:5', 'title' => 'required|string|max:120', 'body' => 'required|string|min:20|max:2000']);
        LegalBookingReview::updateOrCreate(['legal_booking_id' => $booking->id], $v + ['user_id' => $booking->user_id]);
        return back()->with('success', 'Thanks — your review is published.');
    }

    private function cartIds(Request $request): array
    {
        $ids = $request->session()->get('legal_cart', []);
        return is_array($ids) ? array_values(array_filter($ids, 'is_int')) : [];
    }

    private function cityName(string $slug): string
    {
        $name = collect(config('properties.cities.India'))->first(fn ($c) => Str::slug($c) === $slug);
        abort_unless($name, 404);
        return $name;
    }

    private function notifyOwner(LegalBooking $b): void
    {
        $to = config('owner.email');
        if (!$to) return;
        $body = implode("\n", array_filter([
            'New legal services booking ' . $b->reference(),
            'Name: ' . $b->name, 'Phone: +91 ' . $b->phone, $b->email ? 'Email: ' . $b->email : null, 'City: ' . $b->city,
            $b->address ? 'Address: ' . $b->address : null, $b->preferred_at ? 'Preferred time: ' . $b->preferred_at->format('d M Y, g:i A') : null,
            '', ...$b->items->map(fn ($i) => '• ' . $i->name . ' — ' . ($i->price ? PF::full($i->price) : 'price on call'))->all(),
            'Total: ' . PF::full($b->total) . ($b->has_quote_items ? ' + items priced on call' : ''),
            $b->notes ? "\nNotes:\n" . $b->notes : null,
            '', 'All bookings: ' . route('admin.legal.index'),
        ], fn ($v) => $v !== null));
        rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail('Legal booking ' . $b->reference() . ': ' . $b->name, e($body), $b->email))->onQueue('emails')), null);
    }
}
