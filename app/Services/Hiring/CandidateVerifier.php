<?php

namespace App\Services\Hiring;

use App\Models\CandidateVerification;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

// Identity checks for applicants: Surepass when SUREPASS_TOKEN is set, otherwise Sandbox (SANDBOX_API_KEY/SECRET).
// Full PAN and Aadhaar numbers are never stored; only masked references and the provider's verdict.
class CandidateVerifier
{
    public const PAN = '/^[A-Z]{3}[ABCFGHLJPT][A-Z]\d{4}[A-Z]$/';

    public function provider(): ?string
    {
        if (filled(config('services.surepass.token'))) return 'surepass';
        if (filled(config('services.sandbox.api_key')) && filled(config('services.sandbox.api_secret'))) return 'sandbox';
        return null;
    }

    // Instant checks that need no provider: contact verification and duplicate identities.
    public function basicChecks(JobApplication $application): array
    {
        $user = $application->candidate;
        $profile = $user?->candidateProfile;
        $phone = preg_replace('/\D/', '', (string) $profile?->phone);
        $samePhone = strlen($phone) >= 10 ? DB::table('candidate_profiles')->where('user_id', '!=', $user->id)->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'+',''),10) = ?", [substr($phone, -10)])->count() : 0;
        $sameName = $user ? DB::table('users')->where('id', '!=', $user->id)->where('name', $user->name)->where(fn ($q) => $q->whereNull('role')->orWhereNotIn('role', ['employer', 'admin']))->count() : 0;
        return [
            ['label' => 'Email verified', 'ok' => (bool) $user?->email_verified_at, 'detail' => $user?->email],
            ['label' => 'Phone verified (OTP)', 'ok' => (bool) $profile?->phone_confirmed_at, 'detail' => $profile?->phone ?: 'No phone on profile'],
            ['label' => 'No duplicate phone', 'ok' => $samePhone === 0, 'detail' => $samePhone ? $samePhone . ' other account(s) use this phone' : 'Unique'],
            ['label' => 'Unique name', 'ok' => $sameName === 0, 'detail' => $sameName ? $sameName . ' other account(s) share this exact name' : 'Unique'],
            ['label' => 'Resume on file', 'ok' => (bool) ($application->resume_id || ($profile && DB::table('resumes')->where('candidate_profile_id', $profile->id)->exists())), 'detail' => ''],
            ['label' => 'Profile completeness', 'ok' => (int) $profile?->profile_completion >= 70, 'detail' => (int) $profile?->profile_completion . '%'],
        ];
    }

    public function pan(JobApplication $application, int $by, string $pan, string $name): CandidateVerification
    {
        $pan = strtoupper(preg_replace('/\s+/', '', $pan));
        $masked = substr($pan, 0, 3) . '****' . substr($pan, -3);
        if (!preg_match(self::PAN, $pan)) return $this->record($application, $by, 'pan', $masked, 'format', 'failed', 'PAN format is invalid (5 letters, 4 digits, 1 letter).');
        $provider = $this->provider();
        if (!$provider) return $this->record($application, $by, 'pan', $masked, 'format', 'pending', 'PAN format is valid. Add SUREPASS_TOKEN or SANDBOX keys in .env to confirm it with the Income Tax database.');
        try {
            if ($provider === 'surepass') {
                $data = $this->surepass(config('services.surepass.paths.pan', 'pan/pan'), ['id_number' => $pan]);
                $holder = (string) ($data['full_name'] ?? $data['name'] ?? '');
                $valid = $holder !== '';
            } else {
                $data = $this->sandbox('/kyc/pan/verify', ['@entity' => 'in.co.sandbox.kyc.pan_verification.request', 'pan' => $pan, 'name_as_per_pan' => $name, 'consent' => 'Y', 'reason' => 'Pre-employment background verification']);
                $valid = strcasecmp((string) ($data['status'] ?? ''), 'valid') === 0;
                $holder = ($data['name_as_per_pan_match'] ?? false) ? $name : '';
            }
            $match = $holder !== '' && $this->nameMatches($holder, $name);
            return $this->record($application, $by, 'pan', $masked, $provider, $valid && $match ? 'passed' : 'failed',
                !$valid ? 'PAN is not valid.' : ($match ? 'PAN valid; name matches the candidate.' : 'PAN valid but the name on PAN does not match' . ($holder ? ' (' . $holder . ')' : '') . '.'),
                ['name_on_pan' => $holder ?: null, 'aadhaar_linked' => $data['aadhaar_linked'] ?? $data['aadhaar_seeding_status'] ?? null]);
        } catch (Throwable $e) {
            report($e);
            return $this->record($application, $by, 'pan', $masked, $provider, 'pending', 'Provider did not answer; try again shortly.');
        }
    }

    // Step 1 of Aadhaar OTP: UIDAI sends an OTP to the candidate's Aadhaar-linked mobile.
    public function aadhaarStart(JobApplication $application, int $by, string $aadhaar): CandidateVerification
    {
        $aadhaar = preg_replace('/\D/', '', $aadhaar);
        $masked = 'XXXX-XXXX-' . substr($aadhaar, -4);
        if (strlen($aadhaar) !== 12 || in_array($aadhaar[0], ['0', '1'], true)) return $this->record($application, $by, 'aadhaar', $masked, 'format', 'failed', 'Aadhaar must be 12 digits and cannot start with 0 or 1.');
        $provider = $this->provider();
        if (!$provider) return $this->record($application, $by, 'aadhaar', $masked, 'format', 'pending', 'Aadhaar format is valid. Add SUREPASS_TOKEN or SANDBOX keys in .env to run the OTP check.');
        try {
            $ref = $provider === 'surepass'
                ? ($this->surepass(config('services.surepass.paths.aadhaar_otp', 'aadhaar-v2/generate-otp'), ['id_number' => $aadhaar])['client_id'] ?? null)
                : ($this->sandbox('/kyc/aadhaar/okyc/otp', ['@entity' => 'in.co.sandbox.kyc.aadhaar.okyc.otp.request', 'aadhaar_number' => $aadhaar, 'consent' => 'y', 'reason' => 'Pre-employment background verification'])['reference_id'] ?? null);
            if (!$ref) return $this->record($application, $by, 'aadhaar', $masked, $provider, 'failed', 'UIDAI could not send an OTP for this Aadhaar.');
            $v = $this->record($application, $by, 'aadhaar', $masked, $provider, 'pending', 'OTP sent to the candidate\'s Aadhaar-linked mobile. Enter it to finish.');
            Cache::put('hiring:aadhaar-ref:' . $v->id, (string) $ref, now()->addMinutes(10));
            return $v;
        } catch (Throwable $e) {
            report($e);
            return $this->record($application, $by, 'aadhaar', $masked, $provider, 'failed', 'Provider did not answer; try again shortly.');
        }
    }

    public function aadhaarFinish(CandidateVerification $v, string $otp): CandidateVerification
    {
        $ref = Cache::pull('hiring:aadhaar-ref:' . $v->id);
        if (!$ref) { $v->update(['status' => 'failed', 'message' => 'OTP expired. Start the Aadhaar check again.']); return $v; }
        try {
            $data = $v->provider === 'surepass'
                ? $this->surepass(config('services.surepass.paths.aadhaar_submit', 'aadhaar-v2/submit-otp'), ['client_id' => $ref, 'otp' => trim($otp)])
                : $this->sandbox('/kyc/aadhaar/okyc/otp/verify', ['@entity' => 'in.co.sandbox.kyc.aadhaar.okyc.request', 'reference_id' => $ref, 'otp' => trim($otp)]);
            $holder = (string) ($data['full_name'] ?? $data['name'] ?? '');
            $candidate = (string) DB::table('users')->where('id', $v->candidate_id)->value('name');
            $match = $holder !== '' && $this->nameMatches($holder, $candidate);
            $v->update(['status' => $holder !== '' && $match ? 'passed' : 'failed', 'message' => $holder === '' ? 'Aadhaar could not be confirmed.' : ($match ? 'Aadhaar verified; name matches the candidate.' : 'Aadhaar verified but the name differs (' . $holder . ').'),
                'payload' => ['name_on_aadhaar' => $holder ?: null, 'dob' => $data['dob'] ?? $data['date_of_birth'] ?? null, 'gender' => $data['gender'] ?? null, 'state' => $data['address']['state'] ?? null]]);
        } catch (Throwable $e) {
            report($e);
            $v->update(['status' => 'failed', 'message' => 'OTP was rejected or the provider did not answer.']);
        }
        return $v;
    }

    // EPFO employment history by UAN (Surepass only). Lists past employers with joining/exit dates
    // and checks the candidate's claimed current company against it.
    public function uan(JobApplication $application, int $by, string $uan): CandidateVerification
    {
        $uan = preg_replace('/\D/', '', $uan);
        $masked = str_repeat('X', 8) . substr($uan, -4);
        if (strlen($uan) !== 12) return $this->record($application, $by, 'uan', $masked, 'format', 'failed', 'UAN must be 12 digits.');
        if (!filled(config('services.surepass.token'))) return $this->record($application, $by, 'uan', $masked, 'format', 'pending', 'UAN format is valid. Add SUREPASS_TOKEN in .env to fetch the EPFO employment history.');
        try {
            $data = $this->surepass(config('services.surepass.paths.uan', 'income/employment-history-uan'), ['id_number' => $uan]);
            $rows = $this->employmentRows($data);
            if (!$rows) return $this->record($application, $by, 'uan', $masked, 'surepass', 'failed', 'No EPFO employment records were returned for this UAN.', ['raw_keys' => array_keys($data)]);
            $claimed = (string) $application->candidate?->candidateProfile?->current_company;
            $match = $claimed !== '' && collect($rows)->contains(fn ($row) => $this->companyMatches($row['employer'], $claimed));
            $holder = (string) ($data['name'] ?? $data['full_name'] ?? $data['employee_name'] ?? '');
            $nameOk = $holder === '' || $this->nameMatches($holder, (string) $application->candidate?->name);
            return $this->record($application, $by, 'uan', $masked, 'surepass', $nameOk && ($match || $claimed === '') ? 'passed' : 'failed',
                count($rows) . ' employer record(s) found.' . ($claimed !== '' ? ($match ? ' Current company "' . $claimed . '" confirmed.' : ' Claimed company "' . $claimed . '" not found in EPFO records.') : '') . ($nameOk ? '' : ' Name on EPFO (' . $holder . ') differs.'),
                ['employers' => $rows, 'name_on_epfo' => $holder ?: null]);
        } catch (Throwable $e) {
            report($e);
            return $this->record($application, $by, 'uan', $masked, 'surepass', 'pending', 'Provider did not answer; try again shortly.');
        }
    }

    // Finds the list of employment entries in a provider response, whatever its exact keys.
    private function employmentRows(array $data): array
    {
        $isList = fn (array $a) => $a === [] || array_keys($a) === range(0, count($a) - 1);
        $list = collect($data)->first(fn ($v) => is_array($v) && $isList($v) && isset($v[0]) && is_array($v[0])) ?? ($isList($data) ? $data : []);
        $pick = fn (array $row, array $needles) => collect($row)->first(fn ($v, $k) => is_scalar($v) && collect($needles)->contains(fn ($n) => str_contains(strtolower((string) $k), $n)));
        return collect($list)->filter(fn ($r) => is_array($r))->map(fn ($r) => [
            'employer' => (string) ($pick($r, ['establishment_name', 'employer_name', 'company', 'establishment', 'employer']) ?? ''),
            'joined' => $pick($r, ['date_of_joining', 'doj', 'joining']), 'left' => $pick($r, ['date_of_exit', 'doe', 'exit', 'leaving']),
        ])->filter(fn ($r) => $r['employer'] !== '')->values()->take(30)->all();
    }

    private function companyMatches(string $epfo, string $claimed): bool
    {
        $norm = fn ($s) => trim(preg_replace('/\b(PRIVATE|PVT|LIMITED|LTD|LLP|INDIA|THE|AND|CO|COMPANY|TECHNOLOGIES|TECHNOLOGY|SOLUTIONS|SERVICES)\b/', '', preg_replace('/[^A-Z ]/', ' ', strtoupper($s))));
        $x = preg_replace('/\s+/', ' ', $norm($epfo)); $y = preg_replace('/\s+/', ' ', $norm($claimed));
        if ($x === '' || $y === '') return false;
        similar_text($x, $y, $pct);
        return $pct >= 75 || str_contains($x, $y) || str_contains($y, $x);
    }

    private function nameMatches(string $a, string $b): bool
    {
        $norm = fn ($s) => collect(preg_split('/\s+/', strtoupper(preg_replace('/[^A-Za-z ]/', ' ', $s))))->filter(fn ($t) => strlen($t) > 1)->sort()->implode(' ');
        $x = $norm($a); $y = $norm($b);
        if ($x === '' || $y === '') return false;
        similar_text($x, $y, $pct);
        return $pct >= 70 || str_contains($x, $y) || str_contains($y, $x);
    }

    private function record(JobApplication $application, int $by, string $type, ?string $ref, string $provider, string $status, string $message, array $payload = []): CandidateVerification
    {
        return CandidateVerification::create(['job_application_id' => $application->id, 'candidate_id' => $application->user_id, 'requested_by' => $by, 'type' => $type, 'reference' => $ref,
            'provider' => $provider, 'status' => $status, 'message' => Str::limit($message, 250), 'payload' => $payload ?: null, 'consent_at' => now()]);
    }

    private function surepass(string $path, array $body): array
    {
        $r = Http::withToken(config('services.surepass.token'))->acceptJson()->timeout(30)->post(rtrim(config('services.surepass.base_url'), '/') . '/' . ltrim($path, '/'), $body);
        if (!$r->successful() && $r->status() >= 500) throw new \RuntimeException('Surepass ' . $path . ' returned ' . $r->status());
        return (array) ($r->json('data') ?? []);
    }

    private function sandbox(string $path, array $body): array
    {
        $base = rtrim((string) config('services.sandbox.base_url', 'https://api.sandbox.co.in'), '/');
        $headers = ['x-api-key' => config('services.sandbox.api_key'), 'x-api-version' => config('services.sandbox.api_version', '1.0')];
        $token = Cache::remember('sandbox:access-token:' . md5((string) config('services.sandbox.api_key')), now()->addHours(23), function () use ($base, $headers) {
            $r = Http::withHeaders($headers + ['x-api-secret' => config('services.sandbox.api_secret')])->acceptJson()->timeout(30)->post($base . '/authenticate');
            return $r->json('access_token') ?? $r->json('data.access_token') ?? throw new \RuntimeException('Sandbox login failed: ' . $r->status());
        });
        $r = Http::withHeaders($headers + ['authorization' => $token])->acceptJson()->timeout(30)->post($base . $path, $body);
        if (!$r->successful() && $r->status() >= 500) throw new \RuntimeException('Sandbox ' . $path . ' returned ' . $r->status());
        return (array) ($r->json('data') ?? []);
    }
}
