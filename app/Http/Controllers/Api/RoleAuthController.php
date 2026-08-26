<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoleAuthController extends Controller
{
    public function studentRegister(Request $request): JsonResponse
    {
        $data = $this->registrationData($request);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data + ['role' => 'student', 'role_code' => 1, 'email_verified_at' => now()]);
            $parts = preg_split('/\s+/', trim($user->name), 2);
            $user->candidateProfile()->create(['first_name' => $parts[0], 'last_name' => $parts[1] ?? null, 'profile_completion' => 15]);
            return $user;
        });
        return $this->tokenResponse($user, 'student-api');
    }

    public function employerRegister(Request $request): JsonResponse
    {
        $data = $this->registrationData($request);
        $companyData = $request->validate(['company_name' => ['required','string','max:190'], 'company_website' => ['nullable','url','max:255']]);
        $user = DB::transaction(function () use ($data, $companyData) {
            $user = User::create($data + ['role' => 'employer', 'role_code' => 0, 'email_verified_at' => now()]);
            $company = Company::firstOrCreate(['name' => $companyData['company_name']], ['slug' => Str::slug($companyData['company_name']), 'website' => $companyData['company_website'] ?? null, 'country' => 'India', 'is_active' => true]);
            $user->employerProfile()->create(['company_id' => $company->id, 'verification_status' => 'pending']);
            return $user;
        });
        return $this->tokenResponse($user, 'employer-api');
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
        return $this->tokenResponse($user, $data['device_name'] ?? $role.'-api');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Token revoked.']);
    }

    private function registrationData(Request $request): array
    {
        $data = $request->validate(['name' => ['required','string','max:100'], 'email' => ['required','email','max:190','unique:users,email'], 'password' => ['required','string','min:8','confirmed']]);
        $data['password'] = Hash::make($data['password']);
        return $data;
    }

    private function tokenResponse(User $user, string $name): JsonResponse
    {
        return response()->json(['token_type' => 'Bearer', 'access_token' => $user->createToken($name)->plainTextToken, 'user' => $user], 201);
    }
}
