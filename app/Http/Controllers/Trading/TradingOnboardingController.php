<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\TradingClient;
use App\Models\Trading\TradingClientDocument;
use App\Models\Trading\TradingClientNominee;
use App\Models\Trading\TradingJointHolder;
use App\Models\Trading\TradingMinorMajor;
use App\Services\Trading\KycService;
use App\Services\Trading\TradingAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Client onboarding wizard (KYC), documents on the private disk, and the minor-to-major request. */
class TradingOnboardingController extends Controller
{
    public const STEPS = [
        1 => 'Personal details', 2 => 'Email and mobile', 3 => 'PAN and Aadhaar', 4 => 'Address', 5 => 'Bank account',
        6 => 'Holding, joint holders and minor', 7 => 'Nominees', 8 => 'Documents', 9 => 'Risk profile', 10 => 'Review and submit',
    ];
    public const DOCUMENTS = [
        'photo' => 'Photograph', 'signature' => 'Signature', 'pan_card' => 'PAN card', 'aadhaar_masked' => 'Masked Aadhaar (first 8 digits hidden)',
        'address_proof' => 'Address proof (if different from Aadhaar)', 'bank_proof' => 'Cancelled cheque or bank statement',
        'birth_certificate' => 'Birth certificate (minor)', 'guardian_id' => "Guardian's PAN / ID (minor)", 'nominee_id' => "Nominee's ID proof", 'major_pan' => 'PAN after turning 18',
    ];
    public const RISK_QUESTIONS = [
        'horizon' => ['How long will you stay invested?', ['Less than 1 year' => 1, '1–3 years' => 2, '3–5 years' => 3, 'More than 5 years' => 4]],
        'drop' => ['If your investment fell 20% in a month, you would…', ['Sell everything' => 1, 'Sell some' => 2, 'Wait' => 3, 'Invest more' => 4]],
        'goal' => ['Your main goal', ['Protect capital' => 1, 'Regular income' => 2, 'Balanced growth' => 3, 'Maximum growth' => 4]],
        'experience' => ['Investing experience', ['None' => 1, 'Fixed deposits only' => 2, 'Mutual funds' => 3, 'Stocks and derivatives' => 4]],
        'income_share' => ['Share of monthly income you can invest', ['Under 10%' => 1, '10–20%' => 2, '20–35%' => 3, 'Over 35%' => 4]],
    ];

    public function __construct(private KycService $kyc, private TradingAudit $audit) {}

    public function show(Request $request)
    {
        $client = $this->client($request);
        $step = (int) $request->query('step', $client->status === 'draft' ? $client->step : 10);
        $step = max(1, min(10, $step));
        $client->load(['nominees', 'jointHolders', 'documents', 'verifications']);
        return view('trading.onboarding', ['client' => $client, 'step' => $step, 'steps' => self::STEPS, 'documents' => self::DOCUMENTS,
            'riskQuestions' => self::RISK_QUESTIONS, 'smsReady' => $this->kyc->smsReady(), 'minorMajor' => TradingMinorMajor::where('trading_client_id', $client->id)->latest('id')->first()]);
    }

    public function save(Request $request, int $step)
    {
        $client = $this->client($request);
        abort_unless(isset(self::STEPS[$step]), 404);
        if (in_array($client->status, ['submitted', 'verified'], true) && $step !== 8) {
            return back()->withErrors(['step' => 'Your details are ' . $client->status . '. Contact support to change them.']);
        }
        $method = 'step' . $step;
        $message = $this->{$method}($request, $client);
        if ($step < 10) $client->forceFill(['step' => max($client->step, $step + 1)])->save();
        $this->audit->log('onboarding.step' . $step, $client);
        return redirect()->route('trading.onboarding', ['step' => $step < 10 ? $step + 1 : 10])->with('success', $message ?? self::STEPS[$step] . ' saved.');
    }

    private function step1(Request $request, TradingClient $client): ?string
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:120', 'dob' => 'required|date|before:today|after:1900-01-01', 'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'father_or_spouse_name' => 'required|string|max:120', 'mother_name' => 'nullable|string|max:120', 'marital_status' => ['nullable', Rule::in(['single', 'married', 'other'])],
            'residential_status' => ['required', Rule::in(['resident', 'nri', 'foreign_national'])], 'occupation' => 'required|string|max:40', 'annual_income' => 'required|string|max:40',
            'source_of_wealth' => 'nullable|string|max:60', 'is_pep' => 'nullable|boolean', 'tax_resident_elsewhere' => 'nullable|boolean', 'birth_place' => 'nullable|string|max:80',
        ]);
        $isMinor = now()->diffInYears($data['dob']) < 18;
        $client->fill($data + ['is_pep' => false, 'tax_resident_elsewhere' => false])->forceFill(['is_minor' => $isMinor])->save();
        return $isMinor ? 'Saved. The investor is a minor: a guardian\'s details are required in step 6.' : null;
    }

    private function step2(Request $request, TradingClient $client): ?string
    {
        $action = $request->input('action');
        if ($action === 'send_email') {
            $data = $request->validate(['email' => 'required|email:rfc|max:150']);
            $this->kyc->sendEmailOtp($client, $data['email']);
            throw $this->stay(2, 'OTP sent to ' . $data['email'] . '.');
        }
        if ($action === 'verify_email') {
            $request->validate(['email_otp' => 'required|digits:6']);
            if (!$this->kyc->verifyEmailOtp($client, $request->input('email_otp'))) throw ValidationException::withMessages(['email_otp' => 'Incorrect or expired OTP.']);
            throw $this->stay(2, 'Email verified.');
        }
        if ($action === 'send_mobile') {
            $data = $request->validate(['mobile' => ['required', 'regex:/^[6-9]\d{9}$/']]);
            if (!$this->kyc->sendMobileOtp($client, $data['mobile'])) {
                $client->forceFill(['mobile' => $data['mobile']])->save();
                throw $this->stay(2, 'Mobile number saved. SMS verification is not switched on yet; our team will confirm it.');
            }
            throw $this->stay(2, 'OTP sent to +91 ' . $data['mobile'] . '.');
        }
        if ($action === 'verify_mobile') {
            $request->validate(['mobile_otp' => 'required|digits:6']);
            if (!$this->kyc->verifyMobileOtp($client, $request->input('mobile_otp'))) throw ValidationException::withMessages(['mobile_otp' => 'Incorrect or expired OTP.']);
            throw $this->stay(2, 'Mobile verified.');
        }
        if (!$client->email_verified_at) throw ValidationException::withMessages(['email' => 'Verify your email first.']);
        if (!$client->mobile) throw ValidationException::withMessages(['mobile' => 'Add your mobile number.']);
        return null;
    }

    private function step3(Request $request, TradingClient $client): ?string
    {
        $data = $request->validate([
            'pan' => ['required', 'string', 'regex:' . KycService::PAN], 'pan_name' => 'required|string|max:120',
            'aadhaar_last4' => 'required|digits:4', 'pan_aadhaar_linked' => 'accepted', 'ckyc_number' => 'nullable|digits:14',
        ], ['pan.regex' => 'Enter a valid individual PAN (e.g. ABCPE1234F).', 'pan_aadhaar_linked.accepted' => 'PAN must be linked with Aadhaar to invest.']);
        $pan = strtoupper($data['pan']);
        $hash = TradingClient::hashOf($pan);
        if (TradingClient::where('pan_hash', $hash)->where('id', '!=', $client->id)->exists()) throw ValidationException::withMessages(['pan' => 'This PAN is already registered with another account.']);
        $client->forceFill(['pan' => $pan, 'pan_hash' => $hash, 'pan_name' => $data['pan_name'], 'aadhaar_last4' => $data['aadhaar_last4'], 'aadhaar_method' => 'manual',
            'pan_aadhaar_linked' => true, 'ckyc_number' => $data['ckyc_number'] ?? null])->save();
        $check = $this->kyc->checkPan($client, $pan, $data['pan_name'], $client->dob?->toDateString());
        if ($check->status === 'failed') throw ValidationException::withMessages(['pan' => $check->message]);
        $this->kyc->record($client, 'aadhaar', 'manual', 'pending', 'Last four digits recorded; verified from the masked Aadhaar you upload.');
        return 'PAN saved: ' . $check->message;
    }

    private function step4(Request $request, TradingClient $client): ?string
    {
        $data = $request->validate([
            'address_line1' => 'required|string|max:200', 'address_line2' => 'nullable|string|max:200', 'pincode' => ['required', 'regex:/^[1-9]\d{5}$/'],
            'city' => 'required|string|max:80', 'state' => 'required|string|max:60', 'address_proof_type' => ['required', Rule::in(['aadhaar', 'passport', 'voter_id', 'driving_licence', 'utility_bill', 'bank_statement'])],
            'correspondence_same' => 'nullable|boolean', 'corr_line1' => 'required_without:correspondence_same|nullable|string|max:200', 'corr_pincode' => ['required_without:correspondence_same', 'nullable', 'regex:/^[1-9]\d{5}$/'],
            'corr_city' => 'nullable|string|max:80', 'corr_state' => 'nullable|string|max:60',
        ]);
        $pin = $this->kyc->pincode($data['pincode']);
        if ($pin && strcasecmp($pin['state'], $data['state']) !== 0 && !str_contains(strtolower($pin['state']), strtolower($data['state']))) {
            throw ValidationException::withMessages(['state' => 'PIN ' . $data['pincode'] . ' belongs to ' . $pin['state'] . '.']);
        }
        $same = (bool) ($data['correspondence_same'] ?? false);
        $client->fill(['address_line1' => $data['address_line1'], 'address_line2' => $data['address_line2'] ?? null, 'pincode' => $data['pincode'], 'city' => $data['city'],
            'district' => $pin['district'] ?? null, 'state' => $data['state'], 'address_proof_type' => $data['address_proof_type'], 'correspondence_same' => $same,
            'correspondence_address' => $same ? null : ['line1' => $data['corr_line1'], 'pincode' => $data['corr_pincode'], 'city' => $data['corr_city'] ?? null, 'state' => $data['corr_state'] ?? null],
        ])->forceFill(['address_source' => $data['address_proof_type']])->save();
        $this->kyc->record($client, 'address', 'data.gov.in', $pin ? 'passed' : 'pending', $pin ? 'PIN code matches ' . $pin['district'] . ', ' . $pin['state'] . '.' : 'PIN code will be checked by our team.');
        return null;
    }

    private function step5(Request $request, TradingClient $client): ?string
    {
        $data = $request->validate([
            'bank_account' => ['required', 'regex:/^\d{9,18}$/', 'confirmed'], 'ifsc' => ['required', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'account_type' => ['required', Rule::in(['savings', 'current', 'nre', 'nro'])], 'bank_holder_name' => 'required|string|max:120',
        ], ['bank_account.regex' => 'Account number must be 9 to 18 digits.']);
        $ifsc = strtoupper($data['ifsc']);
        $info = $this->kyc->checkIfsc($client, $ifsc);
        if (!$info) throw ValidationException::withMessages(['ifsc' => 'IFSC not found. Check it on your cheque book.']);
        $client->forceFill(['bank_account' => $data['bank_account'], 'bank_account_hash' => TradingClient::hashOf($data['bank_account']), 'bank_account_last4' => substr($data['bank_account'], -4),
            'ifsc' => $ifsc, 'bank_name' => $info['BANK'] ?? null, 'bank_branch' => $info['BRANCH'] ?? null, 'account_type' => $data['account_type'], 'bank_holder_name' => $data['bank_holder_name'], 'bank_verified_at' => null])->save();
        $check = $this->kyc->checkBank($client, $data['bank_account'], $ifsc, $data['bank_holder_name']);
        return 'Bank saved (' . ($info['BANK'] ?? '') . ', ' . ($info['BRANCH'] ?? '') . '). ' . $check->message;
    }

    private function step6(Request $request, TradingClient $client): ?string
    {
        $data = $request->validate([
            'holding_mode' => ['required', Rule::in(['single', 'joint', 'anyone_or_survivor'])],
            'joint' => 'array|max:2', 'joint.*.name' => 'required_with:joint.*.pan|nullable|string|max:120', 'joint.*.pan' => ['nullable', 'regex:' . KycService::PAN],
            'joint.*.dob' => 'nullable|date|before:today', 'joint.*.relation_with_primary' => 'nullable|string|max:30', 'joint.*.mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'], 'joint.*.email' => 'nullable|email',
            'guardian_name' => [Rule::requiredIf($client->is_minor), 'nullable', 'string', 'max:120'], 'guardian_relation' => [Rule::requiredIf($client->is_minor), 'nullable', Rule::in(['father', 'mother', 'court_appointed'])],
            'guardian_pan' => [Rule::requiredIf($client->is_minor), 'nullable', 'regex:' . KycService::PAN], 'guardian_dob' => [Rule::requiredIf($client->is_minor), 'nullable', 'date', 'before:-18 years'],
        ]);
        $joint = collect($data['joint'] ?? [])->filter(fn ($j) => filled($j['name'] ?? null))->values();
        if ($data['holding_mode'] !== 'single' && $joint->isEmpty()) throw ValidationException::withMessages(['holding_mode' => 'Add at least one joint holder, or choose single holding.']);
        if ($client->is_minor && $data['holding_mode'] !== 'single') throw ValidationException::withMessages(['holding_mode' => 'A minor\'s account can only be held singly (with the guardian).']);
        DB::transaction(function () use ($client, $data, $joint) {
            $client->fill(['holding_mode' => $data['holding_mode']])->forceFill([
                'guardian_name' => $data['guardian_name'] ?? null, 'guardian_relation' => $data['guardian_relation'] ?? null,
                'guardian_pan' => isset($data['guardian_pan']) ? strtoupper($data['guardian_pan']) : null, 'guardian_dob' => $data['guardian_dob'] ?? null,
            ])->save();
            TradingJointHolder::where('trading_client_id', $client->id)->delete();
            if ($data['holding_mode'] === 'single') return;
            foreach ($joint as $i => $j) {
                TradingJointHolder::create(['trading_client_id' => $client->id, 'sequence' => $i + 2, 'name' => $j['name'], 'dob' => $j['dob'] ?? null,
                    'pan' => isset($j['pan']) ? strtoupper($j['pan']) : null, 'pan_hash' => isset($j['pan']) ? TradingClient::hashOf($j['pan']) : null,
                    'relation_with_primary' => $j['relation_with_primary'] ?? null, 'mobile' => $j['mobile'] ?? null, 'email' => $j['email'] ?? null]);
            }
        });
        return null;
    }

    private function step7(Request $request, TradingClient $client): ?string
    {
        if ($request->boolean('opt_out')) {
            $request->validate(['opt_out_declaration' => 'accepted']);
            DB::transaction(function () use ($client) {
                TradingClientNominee::where('trading_client_id', $client->id)->delete();
                $client->forceFill(['nomination_opted' => false, 'nomination_opt_out_at' => now()])->save();
            });
            return 'You have opted out of nomination. You can add nominees any time.';
        }
        $data = $request->validate([
            'nominees' => 'required|array|min:1|max:10', 'nominees.*.name' => 'required|string|max:120', 'nominees.*.relation' => 'required|string|max:30',
            'nominees.*.dob' => 'required|date|before:today', 'nominees.*.share_percent' => 'required|numeric|min:0.01|max:100',
            'nominees.*.id_type' => ['required', Rule::in(['pan', 'aadhaar_last4', 'passport', 'driving_licence'])], 'nominees.*.id_number' => 'required|string|max:20',
            'nominees.*.mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'], 'nominees.*.email' => 'nullable|email', 'nominees.*.address' => 'nullable|string|max:250',
            'nominees.*.guardian_name' => 'nullable|string|max:120', 'nominees.*.guardian_relation' => 'nullable|string|max:30',
        ]);
        $total = round(collect($data['nominees'])->sum('share_percent'), 2);
        if (abs($total - 100) > 0.001) throw ValidationException::withMessages(['nominees' => 'Nominee shares must add up to 100% (now ' . $total . '%).']);
        foreach ($data['nominees'] as $i => $n) {
            if (now()->diffInYears($n['dob']) < 18 && blank($n['guardian_name'] ?? null)) throw ValidationException::withMessages(["nominees.$i.guardian_name" => 'A guardian is required for a minor nominee.']);
        }
        DB::transaction(function () use ($client, $data) {
            TradingClientNominee::where('trading_client_id', $client->id)->delete();
            foreach (array_values($data['nominees']) as $i => $n) {
                TradingClientNominee::create(['trading_client_id' => $client->id, 'sequence' => $i + 1, 'name' => $n['name'], 'relation' => $n['relation'], 'dob' => $n['dob'],
                    'id_type' => $n['id_type'], 'id_number' => $n['id_number'], 'mobile' => $n['mobile'] ?? null, 'email' => $n['email'] ?? null, 'address' => $n['address'] ?? null,
                    'share_percent' => $n['share_percent'], 'is_minor' => now()->diffInYears($n['dob']) < 18, 'guardian_name' => $n['guardian_name'] ?? null, 'guardian_relation' => $n['guardian_relation'] ?? null]);
            }
            $client->forceFill(['nomination_opted' => true, 'nomination_opt_out_at' => null])->save();
        });
        return null;
    }

    private function step8(Request $request, TradingClient $client): ?string
    {
        if ($request->hasFile('document')) {
            $data = $request->validate(['type' => ['required', Rule::in(array_keys(self::DOCUMENTS))], 'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096']);
            $file = $data['document'];
            $path = $file->store('trading-kyc/' . $client->id, 'local');   // private disk: never publicly reachable
            TradingClientDocument::where('trading_client_id', $client->id)->where('type', $data['type'])->where('status', '!=', 'verified')->get()
                ->each(function ($old) { Storage::disk('local')->delete($old->path); $old->delete(); });
            TradingClientDocument::create(['trading_client_id' => $client->id, 'type' => $data['type'], 'path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath())]);
            throw $this->stay(8, self::DOCUMENTS[$data['type']] . ' uploaded.');
        }
        $have = TradingClientDocument::where('trading_client_id', $client->id)->pluck('type')->all();
        $required = array_merge(['photo', 'signature', 'pan_card', 'aadhaar_masked', 'bank_proof'], $client->is_minor ? ['birth_certificate', 'guardian_id'] : []);
        if ($missing = array_diff($required, $have)) throw ValidationException::withMessages(['document' => 'Still needed: ' . implode(', ', array_map(fn ($t) => self::DOCUMENTS[$t], $missing)) . '.']);
        return null;
    }

    private function step9(Request $request, TradingClient $client): ?string
    {
        $rules = [];
        foreach (self::RISK_QUESTIONS as $key => [, $options]) $rules['risk.' . $key] = ['required', Rule::in(array_keys($options))];
        $data = $request->validate($rules);
        $score = 0;
        foreach (self::RISK_QUESTIONS as $key => [, $options]) $score += $options[$data['risk'][$key]];
        $profile = $score <= 9 ? 'conservative' : ($score <= 14 ? 'moderate' : 'aggressive');
        $client->forceFill(['risk_answers' => $data['risk'], 'risk_score' => $score, 'risk_profile' => $profile])->save();
        return 'Your risk profile: ' . ucfirst($profile) . ' (score ' . $score . ' of 20).';
    }

    private function step10(Request $request, TradingClient $client): ?string
    {
        $request->validate(['consent_terms' => 'accepted', 'consent_kyc' => 'accepted', 'consent_fatca' => 'accepted']);
        $missing = [];
        if (!$client->full_name || !$client->dob) $missing[] = self::STEPS[1];
        if (!$client->email_verified_at || !$client->mobile) $missing[] = self::STEPS[2];
        if (!$client->pan) $missing[] = self::STEPS[3];
        if (!$client->pincode) $missing[] = self::STEPS[4];
        if (!$client->bank_account) $missing[] = self::STEPS[5];
        if ($client->nomination_opted === null) $missing[] = self::STEPS[7];
        if (!$client->risk_profile) $missing[] = self::STEPS[9];
        if ($missing) throw ValidationException::withMessages(['consent_terms' => 'Please complete: ' . implode(', ', $missing) . '.']);
        $client->forceFill(['status' => 'submitted', 'consent_at' => now(), 'consent_ip' => $request->ip(), 'step' => 10])->save();
        $this->audit->log('onboarding.submitted', $client);
        return 'Submitted. Our team will verify your documents, usually within one working day; you will be notified by email.';
    }

    /** Minor turning 18: request the change with the new PAN and bank details. */
    public function minorToMajor(Request $request)
    {
        $client = $this->client($request);
        abort_unless($client->is_minor && $client->dob, 404);
        $majorOn = $client->dob->copy()->addYears(18);
        if ($majorOn->isFuture()) return back()->withErrors(['major' => 'The account can be changed from ' . $majorOn->format('d M Y') . '.']);
        $data = $request->validate(['new_pan' => ['required', 'regex:' . KycService::PAN], 'new_bank_account' => ['required', 'regex:/^\d{9,18}$/'], 'new_ifsc' => ['required', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/']]);
        TradingMinorMajor::create(['trading_client_id' => $client->id, 'major_on' => $majorOn, 'status' => 'documents_pending', 'new_pan' => strtoupper($data['new_pan']),
            'new_bank_account' => $data['new_bank_account'], 'new_ifsc' => strtoupper($data['new_ifsc'])]);
        $this->audit->log('minor_major.requested', $client);
        return redirect()->route('trading.onboarding', ['step' => 8])->with('success', 'Request recorded. Upload your PAN (after turning 18) and a new signature here.');
    }

    /** The investor's own document (private disk). */
    public function document(Request $request, TradingClientDocument $document)
    {
        $client = TradingClient::find($document->trading_client_id);
        abort_unless($client && ($client->user_id === $request->user()->id || $request->user()->isOwner()), 404);
        $this->audit->log('document.viewed', $document, ['type' => $document->type], $client->id);
        return Storage::disk('local')->response($document->path, $document->original_name, ['Cache-Control' => 'private, no-store']);
    }

    /** PIN code lookup for the address form (JSON). */
    public function pincode(Request $request)
    {
        return response()->json(['data' => $this->kyc->pincode((string) $request->query('pin'))]);
    }

    /** IFSC lookup for the bank form (JSON). */
    public function ifsc(Request $request)
    {
        $ifsc = strtoupper((string) $request->query('ifsc'));
        return response()->json(['data' => preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc) ? $this->kyc->ifsc($ifsc) : []]);
    }

    private function client(Request $request): TradingClient
    {
        $user = $request->user();
        return TradingClient::firstOrCreate(['user_id' => $user->id], [
            'client_code' => 'DT' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), 'email' => $user->email, 'full_name' => $user->name, 'status' => 'draft', 'step' => 1,
        ]);
    }

    /** Ends a sub-action (OTP send/verify, upload) by returning to the same step with a message. */
    private function stay(int $step, string $message): \Illuminate\Http\Exceptions\HttpResponseException
    {
        return new \Illuminate\Http\Exceptions\HttpResponseException(redirect()->route('trading.onboarding', ['step' => $step])->with('success', $message));
    }
}
