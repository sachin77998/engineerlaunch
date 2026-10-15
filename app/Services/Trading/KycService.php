<?php

namespace App\Services\Trading;

use App\Mail\OtpMail;
use App\Models\Trading\TradingClient;
use App\Models\Trading\TradingKycVerification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * KYC checks for onboarding. Each check is recorded in trading_kyc_verifications with a masked response.
 * - PAN: format and holder type; name and status through Sandbox when its subscription is active, else manual review.
 * - Aadhaar: only the last four digits are collected (with a masked copy as a document); full numbers are never stored.
 * - Bank: IFSC from the public Razorpay IFSC directory; account (penny drop) through Sandbox when active, else cheque review.
 * - Address: PIN code to district and state from the India Post directory on data.gov.in.
 * - Email by OTP; mobile by SMS OTP when an SMS gateway (MSG91) is configured.
 */
class KycService
{
    public const PAN = '/^[A-Z]{3}[ABCFGHLJPT][A-Z]\d{4}[A-Z]$/';

    public function __construct(private TradingAudit $audit) {}

    public function checkPan(TradingClient $client, string $pan, string $nameOnPan, ?string $dob): TradingKycVerification
    {
        $pan = strtoupper(trim($pan));
        if (!preg_match(self::PAN, $pan)) return $this->record($client, 'pan', 'format', 'failed', 'PAN format is invalid (5 letters, 4 digits, 1 letter).');
        if ($pan[3] !== 'P') return $this->record($client, 'pan', 'format', 'failed', 'This is not an individual PAN (4th character must be P).');
        if ($this->sandboxReady()) {
            try {
                $json = $this->sandbox('/kyc/pan/verify', ['@entity' => 'in.co.sandbox.kyc.pan_verification.request', 'pan' => $pan, 'name_as_per_pan' => $nameOnPan,
                    'date_of_birth' => $dob ? date('d/m/Y', strtotime($dob)) : null, 'consent' => 'Y', 'reason' => 'Investor onboarding (KYC)']);
                $data = $json['data'] ?? [];
                $valid = strcasecmp((string) ($data['status'] ?? ''), 'valid') === 0;
                $nameMatch = (bool) ($data['name_as_per_pan_match'] ?? false);
                $client->forceFill(['pan_status' => $data['status'] ?? null, 'pan_aadhaar_linked' => isset($data['aadhaar_seeding_status']) ? $data['aadhaar_seeding_status'] === 'y' : null,
                    'pan_verified_at' => $valid && $nameMatch ? now() : null])->save();
                return $this->record($client, 'pan', 'sandbox', $valid && $nameMatch ? 'passed' : 'failed', $valid ? ($nameMatch ? 'PAN valid; name matches.' : 'PAN valid but the name does not match.') : 'PAN is not valid.',
                    ['status' => $data['status'] ?? null, 'category' => $data['category'] ?? null, 'name_match' => $nameMatch, 'dob_match' => $data['date_of_birth_match'] ?? null]);
            } catch (Throwable $e) {
                report($e);
            }
        }
        return $this->record($client, 'pan', 'format', 'pending', 'PAN format is valid; name and status will be checked by our team.');
    }

    public function checkIfsc(TradingClient $client, string $ifsc): array
    {
        $ifsc = strtoupper(trim($ifsc));
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            $this->record($client, 'ifsc', 'format', 'failed', 'IFSC must be 4 letters, 0, then 6 letters or digits.');
            return [];
        }
        $info = $this->ifsc($ifsc);
        $this->record($client, 'ifsc', 'razorpay_ifsc', $info ? 'passed' : 'failed', $info ? ($info['BANK'] . ', ' . $info['BRANCH']) : 'IFSC not found.');
        return $info;
    }

    /** Public IFSC directory (cached a month). */
    public function ifsc(string $ifsc): array
    {
        return Cache::remember('ifsc:' . $ifsc, now()->addMonth(), function () use ($ifsc) {
            try {
                $r = Http::timeout(15)->get('https://ifsc.razorpay.com/' . rawurlencode($ifsc));
                return $r->successful() && is_array($r->json()) ? array_intersect_key($r->json(), array_flip(['BANK', 'BRANCH', 'CITY', 'DISTRICT', 'STATE', 'ADDRESS', 'MICR', 'IFSC', 'UPI', 'NEFT', 'IMPS', 'RTGS'])) : [];
            } catch (Throwable) {
                return [];
            }
        });
    }

    public function checkBank(TradingClient $client, string $account, string $ifsc, string $holder): TradingKycVerification
    {
        if ($this->sandboxReady()) {
            try {
                $json = $this->sandbox('/bank/' . rawurlencode($ifsc) . '/accounts/' . rawurlencode($account) . '/verify?name=' . rawurlencode($holder), null, 'GET');
                $data = $json['data'] ?? [];
                $exists = (bool) ($data['account_exists'] ?? false);
                $name = (string) ($data['name_at_bank'] ?? '');
                $match = $name !== '' && similar_text(strtoupper($name), strtoupper($holder)) / max(strlen($name), strlen($holder)) > 0.7;
                if ($exists && $match) $client->forceFill(['bank_verified_at' => now()])->save();
                return $this->record($client, 'bank', 'sandbox', $exists && $match ? 'passed' : 'failed', $exists ? ($match ? 'Account verified; name matches.' : 'Account exists but the name differs: ' . $name) : 'Account not found.',
                    ['account_exists' => $exists, 'name_at_bank' => $name]);
            } catch (Throwable $e) {
                report($e);
            }
        }
        return $this->record($client, 'bank', 'manual', 'pending', 'Bank account will be verified from the cancelled cheque or statement you upload.');
    }

    /** District and state for a PIN code (India Post directory on data.gov.in). */
    public function pincode(string $pin): array
    {
        $key = config('services.datagov.key');
        if (!preg_match('/^[1-9]\d{5}$/', $pin) || !$key) return [];
        return Cache::remember('pincode-full:' . $pin, now()->addYear(), function () use ($pin, $key) {
            try {
                $records = Http::timeout(20)->get('https://api.data.gov.in/resource/6176ee09-3d56-4a3b-8115-21841576b2f6', ['api-key' => $key, 'format' => 'json', 'limit' => 50, 'filters[pincode]' => $pin])->json('records', []);
                if (!$records) return [];
                return ['district' => Str::title(strtolower($records[0]['districtname'])), 'state' => Str::title(strtolower($records[0]['statename'])),
                    'offices' => collect($records)->pluck('officename')->map(fn ($o) => trim(preg_replace('/\s+(B\.O|S\.O|H\.O|BO|SO|HO)$/i', '', $o)))->unique()->values()->all()];
            } catch (Throwable) {
                return [];
            }
        });
    }

    public function sendEmailOtp(TradingClient $client, string $email): void
    {
        $otp = app()->environment(['local', 'testing']) ? '123456' : (string) random_int(100000, 999999);
        Cache::put('trading:email-otp:' . $client->id, ['hash' => hash_hmac('sha256', $otp . strtolower($email), (string) config('app.key')), 'email' => strtolower($email), 'tries' => 0], now()->addMinutes(10));
        if (!app()->environment(['local', 'testing'])) Mail::to($email)->queue(new OtpMail($otp, 'Verify your email for Do Trading'));
        $this->audit->log('kyc.email_otp_sent', $client, ['email' => preg_replace('/(?<=.{2}).(?=[^@]*@)/', '*', $email)]);
    }

    public function verifyEmailOtp(TradingClient $client, string $otp): bool
    {
        $state = Cache::get('trading:email-otp:' . $client->id);
        if (!$state || $state['tries'] >= 5) return false;
        $ok = hash_equals($state['hash'], hash_hmac('sha256', trim($otp) . $state['email'], (string) config('app.key')));
        if (!$ok) {
            $state['tries']++;
            Cache::put('trading:email-otp:' . $client->id, $state, now()->addMinutes(10));
            $this->record($client, 'email', 'email_otp', 'failed', 'Wrong OTP.');
            return false;
        }
        Cache::forget('trading:email-otp:' . $client->id);
        $client->forceFill(['email' => $state['email'], 'email_verified_at' => now()])->save();
        $this->record($client, 'email', 'email_otp', 'passed', 'Email verified.');
        return true;
    }

    public function smsReady(): bool
    {
        return filled(config('services.msg91.key')) && filled(config('services.msg91.otp_template'));
    }

    public function sendMobileOtp(TradingClient $client, string $mobile): bool
    {
        if (!$this->smsReady() && !app()->environment(['local', 'testing'])) return false;
        $otp = app()->environment(['local', 'testing']) ? '123456' : (string) random_int(100000, 999999);
        Cache::put('trading:mobile-otp:' . $client->id, ['hash' => hash_hmac('sha256', $otp . $mobile, (string) config('app.key')), 'mobile' => $mobile, 'tries' => 0], now()->addMinutes(10));
        if ($this->smsReady()) {
            Http::timeout(15)->withHeaders(['authkey' => config('services.msg91.key')])->post('https://control.msg91.com/api/v5/otp', [
                'template_id' => config('services.msg91.otp_template'), 'mobile' => '91' . $mobile, 'otp' => $otp,
            ]);
        }
        $this->audit->log('kyc.mobile_otp_sent', $client, ['mobile' => substr($mobile, 0, 2) . '******' . substr($mobile, -2)]);
        return true;
    }

    public function verifyMobileOtp(TradingClient $client, string $otp): bool
    {
        $state = Cache::get('trading:mobile-otp:' . $client->id);
        if (!$state || $state['tries'] >= 5) return false;
        if (!hash_equals($state['hash'], hash_hmac('sha256', trim($otp) . $state['mobile'], (string) config('app.key')))) {
            $state['tries']++;
            Cache::put('trading:mobile-otp:' . $client->id, $state, now()->addMinutes(10));
            $this->record($client, 'mobile', 'sms_otp', 'failed', 'Wrong OTP.');
            return false;
        }
        Cache::forget('trading:mobile-otp:' . $client->id);
        $client->forceFill(['mobile' => $state['mobile'], 'mobile_verified_at' => now()])->save();
        $this->record($client, 'mobile', 'sms_otp', 'passed', 'Mobile verified.');
        return true;
    }

    public function record(TradingClient $client, string $check, string $provider, string $status, ?string $message = null, array $response = []): TradingKycVerification
    {
        $row = TradingKycVerification::create(['trading_client_id' => $client->id, 'check' => $check, 'provider' => $provider, 'status' => $status,
            'message' => Str::limit((string) $message, 250, ''), 'response' => $response ?: null, 'performed_by' => $provider === 'manual' ? auth()->id() : null]);
        $this->audit->log('kyc.' . $check . '.' . $status, $client, ['provider' => $provider]);
        return $row;
    }

    private function sandboxReady(): bool
    {
        return filled(config('services.sandbox.api_key')) && filled(config('services.sandbox.api_secret'));
    }

    private function sandbox(string $path, ?array $body, string $method = 'POST'): array
    {
        $base = rtrim((string) config('services.sandbox.base_url', 'https://api.sandbox.co.in'), '/');
        $headers = ['x-api-key' => config('services.sandbox.api_key'), 'x-api-version' => config('services.sandbox.api_version', '1.0')];
        $token = Cache::remember('sandbox:access-token:' . md5((string) config('services.sandbox.api_key')), now()->addHours(23), function () use ($base, $headers) {
            $r = Http::withHeaders($headers + ['x-api-secret' => config('services.sandbox.api_secret')])->acceptJson()->timeout(30)->post($base . '/authenticate');
            return $r->json('access_token') ?? $r->json('data.access_token') ?? throw new \RuntimeException('Sandbox login failed: ' . $r->status());
        });
        $request = Http::withHeaders($headers + ['authorization' => $token])->acceptJson()->timeout(30);
        $response = $method === 'GET' ? $request->get($base . $path) : $request->post($base . $path, $body);
        if (!$response->successful()) throw new \RuntimeException('Sandbox ' . $path . ' returned ' . $response->status() . ': ' . Str::limit($response->body(), 200));
        return (array) $response->json();
    }
}
