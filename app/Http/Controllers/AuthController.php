<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
class AuthController extends Controller {
 public function registerForm(){return view('auth.register-v2',['stats'=>['jobs'=>\App\Models\Job::active()->count(),'companies'=>\App\Models\Company::active()->whereHas('activeJobs')->count(),'remote'=>\App\Models\Job::active()->where('work_mode','remote')->count()]]);}
 public function register(Request $r){$data=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:190|unique:users','password'=>'required|string|min:8|confirmed','account_type'=>'nullable|in:student,employer']);$code=(string)random_int(100000,999999);DB::table('email_otps')->where('email',$data['email'])->whereNull('used_at')->delete();DB::table('email_otps')->insert(['email'=>$data['email'],'code_hash'=>Hash::make($code),'expires_at'=>now()->addMinutes(10),'created_at'=>now(),'updated_at'=>now()]);session(['pending_registration'=>['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password']),'role'=>$data['account_type']??'student'],'dev_otp'=>app()->isLocal()?$code:null]);Mail::raw("Your EngineerLaunch verification code is {$code}. It expires in 10 minutes.",fn($m)=>$m->to($data['email'])->subject('Verify your EngineerLaunch account'));return redirect()->route('otp.form');}
 public function otpForm(){abort_unless(session('pending_registration'),403);return view('auth.otp',['email'=>session('pending_registration.email'),'devOtp'=>app()->isLocal()?session('dev_otp'):null]);}
 public function verify(Request $r){$r->validate(['code'=>'required|digits:6']);$pending=session('pending_registration');abort_unless($pending,403);$otp=DB::table('email_otps')->where('email',$pending['email'])->whereNull('used_at')->where('expires_at','>',now())->latest()->first();if(!$otp||!Hash::check($r->code,$otp->code_hash))return back()->withErrors(['code'=>'The code is invalid or expired.']);DB::table('email_otps')->where('id',$otp->id)->update(['used_at'=>now()]);$pending['role']=config('services.admin_email')&&strcasecmp($pending['email'],config('services.admin_email'))===0?'admin':($pending['role']??'student');$user=User::create($pending);session()->forget('pending_registration');Auth::login($user);$r->session()->regenerate();return redirect($user->role==='employer'?'/employer':'/dashboard');}
 public function loginForm(){return view('auth.login');}
 public function login(Request $r){$credentials=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($credentials,$r->boolean('remember')))return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended('/dashboard');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
}
