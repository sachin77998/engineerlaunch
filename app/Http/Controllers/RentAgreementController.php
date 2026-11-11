<?php

namespace App\Http\Controllers;

use App\Models\Legal\LegalBooking;
use App\Models\Legal\LegalBookingReview;
use App\Models\Legal\LegalService;
use App\Models\Legal\RentAgreement;
use App\Services\Legal\RentAgreementCost;
use App\Services\Property\PropertyFormat as PF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Online rent agreements: cost calculator, four-step drafting on our own template, printable preview, and ordering as a legal services booking.
class RentAgreementController extends Controller
{
    public function index(Request $request, ?string $city = null)
    {
        if (!$city) return redirect()->route('rent-agreement.index', Str::slug($request->session()->get('legal_city', config('legal.default_city'))));
        $cityName = $this->cityName($city);
        $request->session()->put('legal_city', $cityName);
        $o = config('rent_agreement');
        $services = LegalService::whereIn('slug', [$o['agreement_service'], $o['registration_service'], ...$o['addons']])->where('is_active', true)->get()->keyBy('slug');
        $reviews = LegalBookingReview::where('is_visible', true)->whereHas('booking.items.service', fn ($q) => $q->where('slug', $o['agreement_service']))
            ->with(['user:id,name', 'booking.items'])->latest()->take(9)->get();
        return view('rent-agreement.index', [
            'city' => $cityName, 'citySlug' => $city, 'state' => RentAgreementCost::state($cityName), 'o' => $o, 'services' => $services,
            'cities' => collect(config('properties.cities.India'))->sort()->values(), 'cart' => $request->session()->get('legal_cart', []),
            'reviews' => $reviews, 'drafts' => $request->user() ? RentAgreement::where('user_id', $request->user()->id)->where('status', 'draft')->latest()->take(5)->get() : collect(),
            'other' => LegalService::whereIn('slug', ['tenant-verification', 'affidavit', 'legal-title-opinion'])->where('is_active', true)->get(),
        ]);
    }

    /** Calculator → new draft. */
    public function start(Request $request)
    {
        $o = config('rent_agreement');
        $v = $request->validate([
            'city' => 'required|string|in:' . implode(',', config('properties.cities.India')),
            'rent' => 'required|integer|min:500|max:100000000',
            'deposit' => 'nullable|integer|min:0|max:1000000000',
            'months' => 'required|integer|min:1|max:' . $o['max_months'],
            'addons' => 'nullable|array', 'addons.*' => 'in:' . implode(',', $o['addons']),
            'type' => 'nullable|in:' . implode(',', array_keys($o['types'])),
        ]);
        $state = RentAgreementCost::state($v['city']);
        $a = RentAgreement::create([
            'user_id' => $request->user()->id, 'city' => $v['city'], 'state' => $state, 'rent' => $v['rent'], 'deposit' => $v['deposit'] ?? 0, 'months' => $v['months'],
            'type' => RentAgreementCost::registrationRequired($state, $v['months']) ? 'registered' : ($v['type'] ?? 'notarised'),
            'addons' => $v['addons'] ?? [], 'terms' => ['due_day' => 5, 'notice_months' => 1, 'lockin_months' => 0, 'escalation_pct' => 5, 'maintenance' => 'included'],
        ]);
        return redirect()->route('rent-agreement.step', [$a, 'parties']);
    }

    public function step(Request $request, RentAgreement $agreement, string $step)
    {
        $this->own($request, $agreement);
        if ($agreement->status !== 'draft' && $step !== 'review') return redirect()->route('rent-agreement.preview', $agreement);
        $data = ['a' => $agreement, 'step' => $step, 'o' => config('rent_agreement'), 'steps' => config('rent_agreement.steps')];
        if ($step === 'review') {
            $data['cost'] = RentAgreementCost::breakdown($agreement->city, $agreement->rent, $agreement->deposit, $agreement->months, $agreement->type, $agreement->addons ?? []);
            $data['addonServices'] = LegalService::whereIn('slug', config('rent_agreement.addons'))->where('is_active', true)->get();
        }
        return view('rent-agreement.step', $data);
    }

    public function save(Request $request, RentAgreement $agreement, string $step)
    {
        $this->own($request, $agreement);
        abort_unless($agreement->status === 'draft', 422, 'This agreement is already ordered.');
        $o = config('rent_agreement');
        $person = fn ($p) => [
            "$p.name" => 'required|string|max:120', "$p.phone" => ['required', 'regex:/^[6-9]\d{9}$/'], "$p.email" => 'nullable|email|max:191',
            "$p.address" => 'required|string|max:300', "$p.pan" => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
        ];
        if ($step === 'parties') {
            $v = $request->validate(array_merge(['created_by' => 'required|in:owner,tenant', 'use_draft' => 'nullable|boolean'], $person('landlord'), $person('tenant')),
                ['*.phone.regex' => 'Enter a 10-digit mobile number.', '*.pan.regex' => 'PAN looks like ABCDE1234F.']);
            $agreement->fill(['created_by' => $v['created_by'], 'landlord' => $v['landlord'], 'tenant' => $v['tenant']]);
            $agreement->terms = array_merge($agreement->terms ?? [], ['use_draft' => $request->boolean('use_draft') && $agreement->draft_path]);
            $next = $agreement->usesDraft() ? 'review' : 'property';
            if ($agreement->usesDraft()) {
                $agreement->markDone('property');
                $agreement->markDone('terms');
            }
        } elseif ($step === 'property') {
            $v = $request->validate([
                'usage' => 'required|in:' . implode(',', array_keys($o['usage'])),
                'property.type' => 'required|in:' . implode(',', array_keys($o['property_types'])),
                'property.address' => 'required|string|max:300', 'property.pincode' => 'nullable|digits:6',
                'property.bhk' => 'nullable|integer|min:0|max:20', 'property.furnishing' => 'required|in:' . implode(',', array_keys(config('properties.furnishing'))),
                'fixtures' => 'nullable|array', 'fixtures.*' => 'nullable|integer|min:0|max:99',
            ]);
            $fixtures = collect($v['fixtures'] ?? [])->filter(fn ($n, $k) => $n > 0 && isset(config('properties.furnishings')[$k]))->map(fn ($n) => (int) $n)->all();
            $agreement->fill(['usage' => $v['usage'], 'property' => array_merge($v['property'], ['fixtures' => $fixtures])]);
            $next = 'terms';
        } elseif ($step === 'terms') {
            $v = $request->validate([
                'start_date' => 'required|date|after_or_equal:' . now()->subMonths(2)->toDateString(),
                'months' => 'required|integer|min:1|max:' . $o['max_months'],
                'rent' => 'required|integer|min:500|max:100000000', 'deposit' => 'required|integer|min:0|max:1000000000',
                'terms.due_day' => 'required|integer|min:1|max:28', 'terms.maintenance' => 'required|in:included,tenant',
                'terms.maintenance_amount' => 'nullable|integer|min:0|max:10000000', 'terms.escalation_pct' => 'required|numeric|min:0|max:50',
                'terms.notice_months' => 'required|integer|min:0|max:12', 'terms.lockin_months' => 'required|integer|min:0|max:' . $o['max_months'],
                'terms.pets' => 'nullable|boolean', 'terms.extra' => 'nullable|string|max:3000',
            ]);
            $agreement->fill(['start_date' => $v['start_date'], 'months' => $v['months'], 'rent' => $v['rent'], 'deposit' => $v['deposit']]);
            $agreement->terms = array_merge($agreement->terms ?? [], $v['terms'], ['pets' => $request->boolean('terms.pets')]);
            if (RentAgreementCost::registrationRequired($agreement->state, $agreement->months)) $agreement->type = 'registered';
            $next = 'review';
        } else {
            return $this->order($request, $agreement);
        }
        $agreement->markDone($step);
        $agreement->save();
        return redirect()->route('rent-agreement.step', [$agreement, $next]);
    }

    /** Review step: options change (type, add-ons) without placing the order. */
    public function options(Request $request, RentAgreement $agreement)
    {
        $this->own($request, $agreement);
        abort_unless($agreement->status === 'draft', 422);
        $o = config('rent_agreement');
        $v = $request->validate(['type' => 'required|in:' . implode(',', array_keys($o['types'])), 'addons' => 'nullable|array', 'addons.*' => 'in:' . implode(',', $o['addons']), 'delivery_address' => 'nullable|string|max:255']);
        $agreement->update([
            'type' => RentAgreementCost::registrationRequired($agreement->state, $agreement->months) ? 'registered' : $v['type'],
            'addons' => $v['addons'] ?? [], 'delivery_address' => $v['delivery_address'] ?? null,
        ]);
        return redirect()->route('rent-agreement.step', [$agreement, 'review']);
    }

    public function upload(Request $request, RentAgreement $agreement)
    {
        $this->own($request, $agreement);
        $file = $request->validate(['draft' => 'required|file|mimes:pdf,doc,docx|max:8192'])['draft'];
        if ($agreement->draft_path) Storage::disk('local')->delete($agreement->draft_path);
        $agreement->update([
            'draft_path' => $file->storeAs('rent-agreements/' . $agreement->id, Str::random(32) . '.' . strtolower($file->getClientOriginalExtension()), 'local'),
            'draft_name' => Str::limit($file->getClientOriginalName(), 180, ''), 'terms' => array_merge($agreement->terms ?? [], ['use_draft' => true]),
        ]);
        return redirect()->route('rent-agreement.step', [$agreement, 'parties'])->with('success', 'Draft uploaded. Fill in the landlord and tenant details and we will prepare it on stamp paper.');
    }

    public function draft(Request $request, RentAgreement $agreement)
    {
        $this->own($request, $agreement, true);
        abort_unless($agreement->draft_path && Storage::disk('local')->exists($agreement->draft_path), 404);
        return Storage::disk('local')->download($agreement->draft_path, $agreement->draft_name);
    }

    public function preview(Request $request, RentAgreement $agreement)
    {
        $this->own($request, $agreement, true);
        return view('rent-agreement.preview', ['a' => $agreement]);
    }

    public function destroy(Request $request, RentAgreement $agreement)
    {
        $this->own($request, $agreement);
        abort_unless($agreement->status === 'draft', 422);
        Storage::disk('local')->deleteDirectory('rent-agreements/' . $agreement->id);
        $agreement->delete();
        return redirect()->route('rent-agreement.index')->with('success', 'Draft deleted.');
    }

    private function order(Request $request, RentAgreement $agreement)
    {
        $need = $agreement->usesDraft() ? ['parties'] : ['parties', 'property', 'terms'];
        foreach ($need as $s) if (!$agreement->isDone($s)) return redirect()->route('rent-agreement.step', [$agreement, $s])->with('error', 'Complete ' . strtolower(config('rent_agreement.steps')[$s]) . ' first.');
        if (in_array('doorstep-delivery', $agreement->addons ?? [], true) && !$agreement->delivery_address) return redirect()->route('rent-agreement.step', [$agreement, 'review'])->with('error', 'Add a delivery address for doorstep delivery.');
        $cost = RentAgreementCost::breakdown($agreement->city, $agreement->rent, $agreement->deposit, $agreement->months, $agreement->type, $agreement->addons ?? []);
        $who = $agreement->created_by === 'tenant' ? $agreement->tenant : $agreement->landlord;
        $booking = DB::transaction(function () use ($request, $agreement, $cost, $who) {
            $b = LegalBooking::create([
                'user_id' => $request->user()->id, 'name' => $who['name'], 'phone' => $who['phone'], 'email' => $who['email'] ?? $request->user()->email,
                'city' => $agreement->city, 'address' => $agreement->delivery_address ?? ($agreement->property['address'] ?? null),
                'notes' => 'Rent agreement ' . $agreement->reference() . ' (' . config('rent_agreement.types')[$agreement->type][0] . ', ' . $agreement->months . ' months)'
                    . ($cost['stamp'] !== null ? '. Stamp duty estimate ' . PF::full($cost['stamp']) : '. Stamp duty to be confirmed')
                    . ($cost['registration'] ? ', registration fee estimate ' . PF::full($cost['registration']) : '') . ' — government charges at actuals.',
                'total' => $cost['service_total'], 'has_quote_items' => $cost['on_call'],
            ]);
            foreach ($cost['services'] as $s) $b->items()->create(['legal_service_id' => $s->id, 'name' => $s->name, 'price' => $s->price]);
            $agreement->update(['status' => 'ordered', 'legal_booking_id' => $b->id]);
            return $b;
        });
        $to = config('owner.email');
        if ($to) rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail('Rent agreement order ' . $booking->reference() . ': ' . $booking->name,
            e($booking->notes . "\nPhone: +91 " . $booking->phone . "\nCity: " . $booking->city . "\nPreview: " . route('rent-agreement.preview', $agreement) . "\nAll bookings: " . route('admin.legal.index')), $booking->email))->onQueue('emails')), null);
        return redirect()->route('legal.bookings')->with('success', 'Rent agreement ordered (' . $booking->reference() . '). Our team will call ' . $booking->phone . ' to confirm stamp duty and signing.');
    }

    private function own(Request $request, RentAgreement $a, bool $allowAdmin = false): void
    {
        $u = $request->user();
        $admin = $allowAdmin && ($u->role === 'admin' || $u->role_code === 2) && strcasecmp($u->email, (string) config('owner.email')) === 0;
        abort_unless($a->user_id === $u->id || $admin, 403);
    }

    private function cityName(string $slug): string
    {
        $name = collect(config('properties.cities.India'))->first(fn ($c) => Str::slug($c) === $slug);
        abort_unless($name, 404);
        return $name;
    }
}
