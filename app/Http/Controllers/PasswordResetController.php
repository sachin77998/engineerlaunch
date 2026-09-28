<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function requestForm() { return view('auth.forgot-password'); }

    public function email(Request $request)
    {
        $data = $request->validate(['email'=>'required|email|max:190']);
        if (app()->environment('production') && in_array(config('mail.default'), ['log','array'], true)) {
            return back()->withInput($request->only('email'))->withErrors(['email'=>'Password reset email is not configured. Please contact support.']);
        }
        try {
            Password::sendResetLink(['email'=>trim($data['email'])]);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput($request->only('email'))->withErrors(['email'=>'We could not send the reset email. Please try again shortly.']);
        }
        return back()->with('status','If an account exists for this email, a password reset link will be sent. Check your inbox and spam folder.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token'=>$token, 'email'=>$request->query('email','')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['token'=>'required|string','email'=>'required|email','password'=>'required|string|min:8|max:255|confirmed']);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email'=>'This reset link is invalid or expired. Request a new link.']);
        }
        return redirect()->route('login')->with('status','Your password has been reset. Sign in with your new password.');
    }
}
