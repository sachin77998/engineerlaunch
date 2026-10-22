<?php

namespace App\Http\Controllers;

use App\Models\HiringEmailTemplate;
use App\Models\HiringOffer;
use App\Models\JobApplication;
use App\Services\Hiring\CandidateVerifier;
use App\Services\Hiring\Messenger;
use App\Services\Hiring\OfferLetters;
use App\Services\Hiring\TalentSearch;
use App\Services\Hiring\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Candidate emails, reusable templates, offer letters and the EPFO employment check.
class HiringCommsController extends Controller
{
    public function __construct(private Team $team, private TalentSearch $search) {}

    public function email(Request $r, JobApplication $application, Messenger $messenger)
    {
        $this->own($r, $application, 'email');
        $d = $r->validate(['subject' => 'required|string|max:190', 'body' => 'required|string|max:10000']);
        $m = $messenger->send($application, $r->user(), $d['subject'], $d['body']);
        return back()->with('success', $m ? 'Email queued to ' . $m->to_email . '.' : 'This candidate has no email address.');
    }

    public function saveTemplate(Request $r)
    {
        $this->team->authorize($r->user(), 'email');
        $d = $r->validate(['name' => 'required|string|max:80', 'subject' => 'required|string|max:190', 'body' => 'required|string|max:10000']);
        HiringEmailTemplate::updateOrCreate(['owner_id' => $this->team->ownerId($r->user()), 'name' => $d['name']], $d);
        return back()->with('success', 'Template "' . $d['name'] . '" saved for your team.');
    }

    public function deleteTemplate(Request $r, HiringEmailTemplate $template)
    {
        $this->team->authorize($r->user(), 'email');
        abort_unless((int) $template->owner_id === $this->team->ownerId($r->user()), 403);
        $template->delete();
        return back()->with('success', 'Template removed.');
    }

    public function storeOffer(Request $r, JobApplication $application, OfferLetters $letters)
    {
        $this->own($r, $application, 'offer');
        $d = $r->validate(['designation' => 'required|string|max:150', 'annual_ctc' => 'required|numeric|min:1', 'currency' => 'required|in:INR,USD,EUR,GBP,AED,SGD,CAD,AUD',
            'fixed_pay' => 'nullable|numeric|min:0', 'variable_pay' => 'nullable|numeric|min:0', 'joining_bonus' => 'nullable|numeric|min:0', 'joining_date' => 'required|date|after_or_equal:today',
            'expires_on' => 'required|date|after_or_equal:today', 'work_location' => 'nullable|string|max:190', 'probation_months' => 'nullable|integer|min:0|max:24',
            'terms' => 'nullable|string|max:5000', 'signatory_name' => 'required|string|max:120', 'signatory_title' => 'nullable|string|max:120', 'action' => 'required|in:draft,send']);
        abort_if(($d['fixed_pay'] ?? 0) + ($d['variable_pay'] ?? 0) > $d['annual_ctc'] * 1.0001, 422, 'Fixed plus variable pay cannot exceed the annual CTC.');
        // A new offer replaces any earlier one that is still open.
        $application->offers()->whereIn('status', ['draft', 'sent'])->update(['status' => 'withdrawn', 'token_hash' => null]);
        $offer = $application->offers()->create(collect($d)->except('action')->all() + ['created_by' => $r->user()->id, 'status' => 'draft']);
        $d['action'] === 'send' ? $letters->send($offer, $r->user()) : $letters->render($offer);
        return back()->with('success', $d['action'] === 'send' ? 'Offer letter emailed to the candidate with an accept/decline link.' : 'Offer saved as draft. Preview the PDF, then send it.');
    }

    public function sendOffer(Request $r, HiringOffer $offer, OfferLetters $letters)
    {
        $this->own($r, $offer->application, 'offer');
        abort_unless($offer->status === 'draft', 422, 'Only a draft offer can be sent.');
        $letters->send($offer, $r->user());
        return back()->with('success', 'Offer letter emailed to the candidate.');
    }

    public function withdrawOffer(Request $r, HiringOffer $offer)
    {
        $this->own($r, $offer->application, 'offer');
        abort_unless(in_array($offer->status, ['draft', 'sent'], true), 422);
        $offer->update(['status' => 'withdrawn', 'token_hash' => null]);
        return back()->with('success', 'Offer withdrawn; the candidate\'s link no longer works.');
    }

    public function offerPdf(Request $r, HiringOffer $offer, OfferLetters $letters)
    {
        $this->own($r, $offer->application, 'view');
        if (!$offer->pdf_path || !Storage::disk('local')->exists($offer->pdf_path)) $letters->render($offer);
        return Storage::disk('local')->response($offer->pdf_path, 'Offer-' . $offer->id . '.pdf');
    }

    // Public: the candidate's link from the offer email.
    public function showPublic(string $token)
    {
        $offer = $this->offerByToken($token);
        return view('employer.hiring.offer-public', ['o' => $offer, 'a' => $offer->application, 'token' => $token]);
    }

    public function pdfPublic(string $token, OfferLetters $letters)
    {
        $offer = $this->offerByToken($token);
        if (!$offer->pdf_path || !Storage::disk('local')->exists($offer->pdf_path)) $letters->render($offer);
        return Storage::disk('local')->response($offer->pdf_path, 'Offer-Letter.pdf');
    }

    public function respondPublic(Request $r, string $token, OfferLetters $letters)
    {
        $offer = $this->offerByToken($token);
        abort_if($offer->effective_status === 'expired', 422, 'This offer has expired. Please contact the recruiter.');
        $d = $r->validate(['decision' => 'required|in:accepted,declined', 'note' => 'nullable|string|max:500']);
        $letters->respond($offer, $d['decision'], $d['note'] ?? null);
        return view('employer.hiring.offer-public', ['o' => $offer->fresh(), 'a' => $offer->application, 'token' => null]);
    }

    public function verifyUan(Request $r, JobApplication $application, CandidateVerifier $verifier)
    {
        $this->own($r, $application, 'verify');
        $key = 'hiring-verify:' . $r->user()->id;
        abort_if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 40), 429, 'Verification limit reached; try again in an hour.');
        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);
        $d = $r->validate(['uan' => 'required|string|max:14', 'consent' => 'accepted']);
        return back()->with('success', 'UAN check: ' . $verifier->uan($application, $r->user()->id, $d['uan'])->message);
    }

    private function offerByToken(string $token): HiringOffer
    {
        $offer = HiringOffer::with(['application.candidate', 'application.job.company'])->where('token_hash', hash('sha256', $token))->where('status', 'sent')->first();
        abort_unless($offer, 404, 'This offer link is no longer active.');
        return $offer;
    }

    private function own(Request $r, ?JobApplication $application, string $ability): void
    {
        abort_unless($application && $this->search->owns($r->user(), $application->loadMissing('job')), 403);
        $this->team->authorize($r->user(), $ability);
    }
}
