<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterEmployerRequest;
use App\Services\VerificationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployerRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.employer-register-v2', ['countryCodes' => [
            '+91'=>'🇮🇳 India (+91)', '+1'=>'🇺🇸/🇨🇦 USA/Canada (+1)', '+44'=>'🇬🇧 UK (+44)',
            '+61'=>'🇦🇺 Australia (+61)', '+49'=>'🇩🇪 Germany (+49)', '+33'=>'🇫🇷 France (+33)', '+971'=>'🇦🇪 UAE (+971)',
        ]]);
    }

    public function sendPhoneOtp(Request $request, VerificationOtpService $otpService): JsonResponse
    {
        $testingOtp = $otpService->usesFixedCode();
        $data = $request->validate(['phone_country_code'=>'required|in:+91,+1,+44,+61,+49,+33,+971','company_phone'=>'required|string|max:20']);
        $number = preg_replace('/\D+/', '', $data['company_phone']);
        if ($data['phone_country_code'] === '+91' && !preg_match('/^[6-9]\d{9}$/', $number)) return response()->json(['message'=>'Enter a valid 10-digit Indian mobile number.'], 422);
        if (strlen($number) < 7 || strlen($number) > 15) return response()->json(['message'=>'Enter a valid phone number.'], 422);
        $phone = $data['phone_country_code'].$number;
        if (!$testingOtp) {
            return response()->json(['message'=>'Phone verification is temporarily unavailable because an SMS provider is not configured.'], 503);
        }
        $code = '123456';
        DB::table('phone_otps')->where('phone',$phone)->where('session_id',$request->session()->getId())->whereNull('verified_at')->delete();
        DB::table('phone_otps')->insert(['phone'=>$phone,'code_hash'=>Hash::make($code),'session_id'=>$request->session()->getId(),'expires_at'=>now()->addMinutes(10),'created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['message'=>'Demo verification mode: use OTP 123456.','local_otp'=>'123456']);
    }

    public function verifyPhoneOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['phone_country_code'=>'required','company_phone'=>'required','code'=>'required|digits:6']);
        $phone = '+'.ltrim($data['phone_country_code'],'+').preg_replace('/\D+/', '', $data['company_phone']);
        $otp = DB::table('phone_otps')->where('phone',$phone)->where('session_id',$request->session()->getId())->whereNull('verified_at')->where('expires_at','>',now())->latest()->first();
        if (!$otp || !Hash::check($data['code'],$otp->code_hash)) return response()->json(['message'=>'The phone verification code is invalid or expired.'],422);
        $token = Str::random(64);
        DB::table('phone_otps')->where('id',$otp->id)->update(['verified_at'=>now(),'updated_at'=>now()]);
        $request->session()->put('verified_employer_phone',['phone'=>$phone,'token_hash'=>hash('sha256',$token),'verified_at'=>now()->toIso8601String()]);
        return response()->json(['message'=>'Phone number verified successfully.','verification_token'=>$token]);
    }

    public function store(RegisterEmployerRequest $request, VerificationOtpService $otpService): RedirectResponse
    {
        $data = $request->validated();
        $phone = $data['phone_country_code'].$data['company_phone'];
        $verified = $request->session()->get('verified_employer_phone');
        if (!$verified || !hash_equals($verified['phone']??'', $phone) || !hash_equals($verified['token_hash']??'', hash('sha256',$data['phone_verification_token']))) return back()->withInput()->withErrors(['company_phone'=>'Verify this phone number before creating the employer account.']);
        $registrationToken = Str::random(64);
        $pending = [
            'name'=>trim($data['first_name'].' '.$data['last_name']), 'email'=>$data['email'], 'password'=>Hash::make($data['password']), 'role'=>'employer', 'role_code'=>0,
            'employer_registration'=>collect($data)->except(['password','password_confirmation','phone_verification_token'])->all()+['phone'=>$phone,'registration_token_hash'=>hash('sha256',$registrationToken),'session_id'=>$request->session()->getId(),'phone_verified_at'=>$verified['verified_at']],
        ];
        $testingOtp = $otpService->usesFixedCode();
        $emailCode = $otpService->generate();
        DB::table('email_otps')->where('email',$data['email'])->whereNull('used_at')->delete();
        DB::table('email_otps')->insert(['email'=>$data['email'],'code_hash'=>Hash::make($emailCode),'expires_at'=>now()->addMinutes(10),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('employer_registration_audits')->insert(['session_id'=>$request->session()->getId(),'registration_token_hash'=>hash('sha256',$registrationToken),'ip_hash'=>hash_hmac('sha256',(string)$request->ip(),config('app.key')),'status'=>'pending_email_verification','phone_verified_at'=>$verified['verified_at'],'created_at'=>now(),'updated_at'=>now()]);
        $request->session()->put('pending_registration',$pending);
        $request->session()->put('dev_otp',$testingOtp?$emailCode:null);
        $request->session()->forget('verified_employer_phone');
        try {
            $otpService->sendEmail($data['email'], $emailCode, 'Verify your employer account');
        } catch (\Throwable $exception) {
            report($exception);
            DB::table('email_otps')->where('email', $data['email'])->whereNull('used_at')->delete();
            $request->session()->forget(['pending_registration', 'dev_otp']);

            return back()->withInput($request->except(['password', 'password_confirmation', 'phone_verification_token']))
                ->withErrors(['email' => 'We could not send the verification email. Check the email address and try again.']);
        }
        return redirect()->route('otp.form')->with('mail_warning',$testingOtp?'Demo verification mode: use email OTP 123456.':'Verification code sent to the HR email.');
    }
}
