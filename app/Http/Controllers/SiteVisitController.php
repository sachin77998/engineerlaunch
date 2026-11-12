<?php

namespace App\Http\Controllers;

use App\Events\Property\SiteVisitChanged;
use App\Models\Property\Project;
use App\Models\Property\ProjectLead;
use App\Models\Property\Property;
use App\Models\Property\PropertyLead;
use App\Models\Property\SiteVisit;
use App\Services\Property\VisitSlots;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Site visits: book a slot at a listing or project, reschedule or cancel it, hosts confirm / complete / mark missed,
// and the visitor's "My enquiries" history.
class SiteVisitController extends Controller
{
    public function __construct(private VisitSlots $slots) {}

    public function book(Request $request, string $type, string $slug)
    {
        $place = $this->visitable($type, $slug);
        $user = $request->user();
        $c = config('site_visits');
        $data = $request->validate([
            'visit_at' => 'required|date_format:Y-m-d H:i', 'people' => 'required|integer|min:1|max:' . $c['max_people'],
            'name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'email' => 'nullable|email|max:191',
            'notes' => 'nullable|string|max:500',
        ]);
        if ($place instanceof Property && $place->user_id === $user->id) throw ValidationException::withMessages(['visit_at' => 'This is your own listing.']);
        $existing = SiteVisit::upcoming()->where(['user_id' => $user->id, 'visitable_type' => $place->getMorphClass(), 'visitable_id' => $place->getKey()])->first();
        if ($existing) return redirect()->route('site-visits.index')->with('error', 'You already have a visit booked here on ' . $existing->visit_at->format('d M, g:i A') . ' — reschedule it instead.');
        if (SiteVisit::upcoming()->where('user_id', $user->id)->count() >= $c['max_open_per_user']) {
            throw ValidationException::withMessages(['visit_at' => 'You have ' . $c['max_open_per_user'] . ' upcoming visits. Cancel one to book another.']);
        }
        $at = Carbon::createFromFormat('Y-m-d H:i', $data['visit_at']);
        $visit = DB::transaction(function () use ($place, $at, $data, $user) {
            // Lock the place's bookings so two people can't take the last seat in a slot at the same time.
            SiteVisit::where(['visitable_type' => $place->getMorphClass(), 'visitable_id' => $place->getKey()])->lockForUpdate()->get(['id']);
            if (!$this->slots->isFree($place, $at)) throw ValidationException::withMessages(['visit_at' => 'That slot is no longer available. Please pick another.']);
            $visit = SiteVisit::create(array_merge($data, ['visitable_type' => $place->getMorphClass(), 'visitable_id' => $place->getKey(), 'user_id' => $user->id,
                'visit_at' => $at, 'email' => $data['email'] ?? $user->email]));
            // Keeps the owner's / builder's enquiry lists complete.
            $lead = ['user_id' => $user->id, 'kind' => 'visit', 'name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? $user->email, 'message' => $data['notes'] ?? null];
            $place instanceof Property
                ? PropertyLead::create($lead + ['property_id' => $place->id, 'visit_at' => $at, 'sender_type' => 'individual'])
                : ProjectLead::create($lead + ['project_id' => $place->id]);
            return $visit;
        });
        event(new SiteVisitChanged($visit, 'booked'));
        return redirect()->route('site-visits.index')->with('success', 'Visit requested for ' . $at->format('D d M, g:i A') . '. You will get an email when it is confirmed.');
    }

    public function index(Request $request)
    {
        $all = SiteVisit::with('visitable')->where('user_id', $request->user()->id)->orderByDesc('visit_at')->limit(200)->get();
        [$upcoming, $past] = $all->partition(fn ($v) => $v->changeable());
        return view('properties.visits.index', ['upcoming' => $upcoming->sortBy('visit_at')->values(), 'past' => $past,
            'slotsFor' => $upcoming->mapWithKeys(fn ($v) => [$v->id => $v->visitable ? $this->slots->available($v->visitable, $v->id) : []])]);
    }

    public function reschedule(Request $request, SiteVisit $visit)
    {
        abort_unless($visit->user_id === $request->user()->id, 404);
        $data = $request->validate(['visit_at' => 'required|date_format:Y-m-d H:i']);
        if (!$visit->changeable()) return back()->with('error', 'This visit can no longer be changed.');
        if ($visit->reschedules >= config('site_visits.max_reschedules')) return back()->with('error', 'This visit has been moved too many times — cancel it and book again.');
        $at = Carbon::createFromFormat('Y-m-d H:i', $data['visit_at']);
        DB::transaction(function () use ($visit, $at) {
            SiteVisit::where(['visitable_type' => $visit->visitable_type, 'visitable_id' => $visit->visitable_id])->lockForUpdate()->get(['id']);
            if (!$visit->visitable || !$this->slots->isFree($visit->visitable, $at, $visit->id)) throw ValidationException::withMessages(['visit_at' => 'That slot is no longer available.']);
            $visit->update(['visit_at' => $at, 'status' => 'requested', 'confirmed_at' => null, 'reschedules' => $visit->reschedules + 1]);
        });
        event(new SiteVisitChanged($visit, 'rescheduled'));
        return back()->with('success', 'Visit moved to ' . $at->format('D d M, g:i A') . '.');
    }

    public function cancel(Request $request, SiteVisit $visit)
    {
        abort_unless($visit->user_id === $request->user()->id, 404);
        if (!$visit->changeable()) return back()->with('error', 'This visit can no longer be cancelled.');
        $visit->update(['status' => 'cancelled', 'cancelled_by' => 'visitor']);
        event(new SiteVisitChanged($visit, 'cancelled'));
        return back()->with('success', 'Visit cancelled.');
    }

    /** Visits to the signed-in user's listings. */
    public function hosting(Request $request)
    {
        $ids = Property::withTrashed()->where('user_id', $request->user()->id)->pluck('id');
        $visits = SiteVisit::with('visitable')->where('visitable_type', 'property')->whereIn('visitable_id', $ids)
            ->orderByRaw("status in ('requested','confirmed') desc")->orderBy('visit_at')->limit(300)->get();
        return view('properties.visits.hosting', compact('visits'));
    }

    public function host(Request $request, SiteVisit $visit)
    {
        $place = $visit->visitable;
        $isAdmin = (bool) $request->user()->isOwner();
        abort_unless($place && (($place instanceof Property && $place->user_id === $request->user()->id) || $isAdmin), 404);
        return $this->applyHostAction($request, $visit);
    }

    /** Admin managing a project's visits. */
    public function adminHost(Request $request, Project $project, SiteVisit $visit)
    {
        abort_unless($visit->visitable_type === 'project' && $visit->visitable_id === $project->id, 404);
        return $this->applyHostAction($request, $visit);
    }

    private function applyHostAction(Request $request, SiteVisit $visit)
    {
        $data = $request->validate(['action' => 'required|in:confirm,complete,no_show,cancel', 'host_note' => 'nullable|string|max:500']);
        $allowed = ['confirm' => $visit->isOpen() && $visit->visit_at->isFuture(), 'cancel' => $visit->isOpen(),
            'complete' => $visit->isOpen() && $visit->visit_at->isPast(), 'no_show' => $visit->isOpen() && $visit->visit_at->isPast()];
        if (!$allowed[$data['action']]) return back()->with('error', 'That change is not possible for this visit now.');
        [$status, $change] = ['confirm' => ['confirmed', 'confirmed'], 'complete' => ['completed', 'completed'], 'no_show' => ['no_show', 'no_show'], 'cancel' => ['cancelled', 'cancelled']][$data['action']];
        $visit->update(['status' => $status, 'host_note' => $data['host_note'] ?? $visit->host_note,
            'confirmed_at' => $status === 'confirmed' ? now() : $visit->confirmed_at, 'cancelled_by' => $status === 'cancelled' ? 'host' : $visit->cancelled_by]);
        event(new SiteVisitChanged($visit, $change, 'host'));
        return back()->with('success', 'Visit ' . strtolower(config('site_visits.statuses.' . $status)) . '.');
    }

    /** Everything the user asked about: numbers viewed, messages, callbacks, brochures and visits, newest first. */
    public function enquiries(Request $request)
    {
        $uid = $request->user()->id;
        $listing = PropertyLead::with('property:id,slug,title,city,locality,status,deleted_at')->where('user_id', $uid)->latest()->limit(200)->get()
            ->map(fn ($l) => ['when' => $l->created_at, 'kind' => ['phone' => 'Viewed number', 'message' => 'Message', 'visit' => 'Site visit'][$l->kind] ?? $l->kind,
                'title' => $l->property?->title ?? 'Removed listing', 'url' => $l->property && $l->property->status === 'active' ? route('properties.show', $l->property) : null,
                'detail' => $l->visit_at ? 'Visit ' . $l->visit_at->format('d M, g:i A') : $l->message, 'type' => 'Listing']);
        $project = ProjectLead::with(['project:id,slug,name,is_active', 'unit:id,label,area_sqft'])->where('user_id', $uid)->latest()->limit(200)->get()
            ->map(fn ($l) => ['when' => $l->created_at, 'kind' => ProjectLead::KINDS[$l->kind] ?? $l->kind, 'title' => $l->project?->name ?? 'Removed project',
                'url' => $l->project && $l->project->is_active ? route('projects.show', $l->project) : null,
                'detail' => $l->unit ? $l->unit->label . ', ' . number_format($l->unit->area_sqft) . ' sqft' . ($l->message ? ' — ' . $l->message : '') : $l->message, 'type' => 'Project']);
        return view('properties.visits.enquiries', ['rows' => $listing->concat($project)->sortByDesc('when')->values()]);
    }

    private function visitable(string $type, string $slug): Model
    {
        $model = $type === 'project' ? Project::live()->where('slug', $slug)->first() : Property::live()->where('slug', $slug)->first();
        abort_unless($model, 404);
        return $model;
    }
}
