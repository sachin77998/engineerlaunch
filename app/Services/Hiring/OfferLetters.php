<?php

namespace App\Services\Hiring;

use App\Mail\HiringMail;
use App\Models\HiringOffer;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfferLetters
{
    public function __construct(private Messenger $messenger, private Pipeline $pipeline) {}

    // Renders the offer to a PDF in private storage and returns its path.
    public function render(HiringOffer $offer): string
    {
        $offer->loadMissing(['application.candidate.candidateProfile', 'application.job.company']);
        $path = 'hiring-offers/' . $offer->id . '-' . Str::random(12) . '.pdf';
        Storage::disk('local')->put($path, Pdf::loadView('employer.hiring.offer-pdf', ['o' => $offer, 'a' => $offer->application, 'words' => $this->inWords($offer->annual_ctc, $offer->currency), 'money' => fn ($v) => $this->money((float) $v, $offer->currency)])->setPaper('a4')->setOption('isFontSubsettingEnabled', true)->output());
        if ($offer->pdf_path && $offer->pdf_path !== $path) Storage::disk('local')->delete($offer->pdf_path);
        $offer->forceFill(['pdf_path' => $path])->save();
        return $path;
    }

    public function send(HiringOffer $offer, User $by): void
    {
        $this->render($offer);
        $token = Str::random(48);
        $offer->forceFill(['status' => 'sent', 'sent_at' => now(), 'token_hash' => hash('sha256', $token)])->save();
        $a = $offer->application;
        $link = route('hiring.offer.show', $token);
        $body = "Dear {first_name},\n\nWe are delighted to offer you the position of {$offer->designation} at {company}. Your offer letter is attached.\n\n"
            . 'Annual CTC: ' . $offer->currency . ' ' . number_format($offer->annual_ctc) . "\nJoining date: " . $offer->joining_date->format('d M Y') . "\nPlease respond by: " . $offer->expires_on->format('d M Y')
            . "\n\nAccept or decline your offer here: {$link}\n\nRegards,\n{recruiter_name}";
        $this->messenger->send($a, $by, 'Offer letter: {job_title} at {company}', $body, ['Offer-Letter.pdf' => Storage::disk('local')->path($offer->pdf_path)]);
        if (in_array($a->stage, ['new', 'screening', 'shortlisted', 'interview'], true)) $this->pipeline->move($a, 'offered', $by->id, 'Offer sent: ' . $offer->currency . ' ' . number_format($offer->annual_ctc));
    }

    public function respond(HiringOffer $offer, string $decision, ?string $note): void
    {
        $offer->forceFill(['status' => $decision, 'responded_at' => now(), 'response_note' => $note ? mb_substr($note, 0, 500) : null, 'token_hash' => null])->save();
        $a = $offer->application()->with(['candidate', 'job'])->first();
        if ($decision === 'declined') $this->pipeline->move($a, 'withdrawn', $a->user_id, 'Candidate declined the offer' . ($note ? ': ' . $note : ''));
        else DB::table('application_status_history')->insert(['job_application_id' => $a->id, 'changed_by' => $a->user_id, 'from_status' => $a->status, 'to_status' => $a->status, 'note' => 'Candidate accepted the offer' . ($note ? ': ' . $note : ''), 'created_at' => now()]);
        $recruiter = User::find($offer->created_by);
        if ($recruiter?->email) {
            $body = "{$a->candidate?->name} has " . $decision . " the offer for {$a->job?->title}." . ($note ? "\n\nTheir note: {$note}" : '') . "\n\nOpen the candidate: " . route('employer.hiring.candidate', $a);
            Mail::to($recruiter->email)->queue((new HiringMail('Offer ' . $decision . ': ' . $a->candidate?->name . ' — ' . $a->job?->title, $body))->onQueue('emails'));
        }
        rescue(fn () => app(Workflows::class)->fire('offer.' . $decision, $a), null);
    }

    // INR uses lakh/crore grouping (12,50,000); other currencies use thousands.
    public function money(float $amount, string $currency = 'INR'): string
    {
        $n = (string) (int) round($amount);
        if ($currency !== 'INR' || strlen($n) <= 3) return $currency . ' ' . number_format((float) $n);
        $last = substr($n, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3));
        return 'INR ' . $rest . ',' . $last;
    }

    // Indian-system amount in words (lakh / crore) for the letter, e.g. "Twelve Lakh Fifty Thousand Rupees".
    public function inWords(float $amount, string $currency = 'INR'): string
    {
        $n = (int) round($amount);
        if ($n === 0) return 'Zero';
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $two = fn ($x) => $x < 20 ? $ones[$x] : trim($tens[intdiv($x, 10)] . ' ' . $ones[$x % 10]);
        $three = fn ($x) => trim(($x >= 100 ? $ones[intdiv($x, 100)] . ' Hundred ' : '') . $two($x % 100));
        $parts = [];
        if ($currency === 'INR') {
            foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $unit => $label) if ($n >= $unit) { $parts[] = ($unit === 10000000 ? $this->inWords(intdiv($n, $unit), 'INR') : $two(intdiv($n, $unit))) . ' ' . $label; $n %= $unit; }
        } else {
            foreach ([1000000000 => 'Billion', 1000000 => 'Million', 1000 => 'Thousand'] as $unit => $label) if ($n >= $unit) { $parts[] = $three(intdiv($n, $unit)) . ' ' . $label; $n %= $unit; }
        }
        if ($n) $parts[] = $three($n);
        return trim(implode(' ', $parts));
    }
}
