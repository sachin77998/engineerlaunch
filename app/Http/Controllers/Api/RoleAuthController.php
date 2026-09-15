<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\VerificationOtpService;

class RoleAuthController extends Controller
{
    public function studentRegister(Request $request, VerificationOtpService $otpService): JsonResponse
    {
        $data = $this->registrationData($request);
        return $this->beginRegistration($data, 'student', [], $otpService);
    }
    public function employerRegister(Request $request, VerificationOtpService $otpService): JsonResponse
    {
        $data = $this->registrationData($request);
        $companyData = $request->validate(['company_name' => ['required','string','max:190'], 'company_website' => ['nullable','url','max:255']]);
        return $this->beginRegistration($data, 'employer', $companyData, $otpService);
    }
    public function verifyRegistration(Request $request): JsonResponse
    {
        $data = $request->validate(['verification_token' => ['required','string','size:64'], 'code' => ['required','digits:6']]);
        $key = 'api-registration:'.hash('sha256', $data['verification_token']);
        $pending = Cache::get($key);
        if (!is_array($pending)) return response()->json(['message' => 'The registration request has expired.'], 422);
        if (($pending['attempts'] ?? 0) >= 5) {
            Cache::forget($key);
            return response()->json(['message' => 'Too many incorrect attempts. Please register again.'], 429);
        }
        if (!Hash::check($data['code'], $pending['code_hash'])) {
            $pending['attempts'] = ($pending['attempts'] ?? 0) + 1;
            Cache::put($key, $pending, now()->addMinutes(10));
            return response()->json(['message' => 'The verification code is invalid.'], 422);
        }
        if (User::where('email', $pending['user']['email'])->exists()) {
            Cache::forget($key);
            return response()->json(['message' => 'An account already exists for this email address.'], 422);
        }
        $user = DB::transaction(function () use ($pending) {
            $role = $pending['role'];
            $user = User::create($pending['user'] + ['role' => $role, 'role_code' => $role === 'employer' ? 0 : 1]);
            $user->email_verified_at = now();
            $user->save();
            if ($role === 'student') {
                $parts = preg_split('/\s+/', trim($user->name), 2);
                $user->candidateProfile()->create(['first_name' => $parts[0], 'last_name' => $parts[1] ?? null, 'profile_completion' => 15]);
            } else {
                $companyData = $pending['company'];
                $company = Company::create(['name' => $companyData['company_name'], 'slug' => Str::slug($companyData['company_name']).'-'.Str::lower(Str::random(6)), 'website' => $companyData['company_website'] ?? null, 'country' => 'India', 'is_active' => true]);
                $user->employerProfile()->create(['company_id' => $company->id, 'verification_status' => 'pending']);
            }
            return $user;
        });
        Cache::forget($key);
        return $this->tokenResponse($user, $pending['role'].'-api');
    }
    public function ownerRegister(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role_code === 2 || $request->user()?->role === 'admin', 403);
        $data = $this->registrationData($request);
        $user = User::create($data + ['role' => 'admin', 'role_code' => 2, 'email_verified_at' => now()]);
        $user->ownerProfile()->create();
        return response()->json(['data' => $user], 201);
    }
    public function login(Request $request, string $role): JsonResponse
    {
        abort_unless(in_array($role, ['student','employer','owner'], true), 404);
        $data = $request->validate(['email' => ['required','email'], 'password' => ['required','string'], 'device_name' => ['nullable','string','max:100']]);
        $user = User::where('email', $data['email'])->first();
        $expected = ['student' => 1, 'employer' => 0, 'owner' => 2][$role];
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->role_code !== $expected) {
            return response()->json(['message' => 'Invalid credentials for this portal.'], 422);
        }
        if (! $user->email_verified_at) {
            return response()->json(['message' => 'Verify your email address before signing in.'], 403);
        }
        return $this->tokenResponse($user, $data['device_name'] ?? $role.'-api');
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Token revoked.']);
    }
    private function beginRegistration(array $data, string $role, array $companyData, VerificationOtpService $otpService): JsonResponse
    {
        $token = Str::random(64);
        $code = $otpService->generate();
        try {
            $otpService->sendEmail($data['email'], $code, 'Verify your Ascendia account');
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'We could not send the verification email. Please try again shortly.'], 503);
        }
        Cache::put('api-registration:'.hash('sha256', $token), [
            'user' => $data,
            'role' => $role,
            'company' => $companyData,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));
        return response()->json(array_filter([
            'message' => 'Enter the six-digit code sent to your email address.',
            'verification_token' => $token,
            'expires_in' => 600,
            'development_code' => $otpService->usesFixedCode() ? $code : null,
        ]), 202);
    }

    private function registrationData(Request $request): array
    {
        $data = $request->validate(['name' => ['required','string','max:100'], 'email' => ['required','email:rfc','max:190','unique:users,email'], 'password' => ['required','string','min:8','confirmed']]);
        $data['password'] = Hash::make($data['password']);
        return $data;
    }

    private function tokenResponse(User $user, string $name): JsonResponse
    {
        return response()->json(['token_type' => 'Bearer', 'access_token' => $user->createToken($name)->plainTextToken, 'user' => $user], 201);
    }
}
