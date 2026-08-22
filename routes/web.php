<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\AdminJobApprovalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('portal-v2', [
        'dsaTracks' => config('interview.dsa', []),
    ]);
})->name('home');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');

Route::get('/learn', fn () => view('learning.hub', ['tracks' => config('learning_tracks')]))->name('learning.index');
Route::get('/learn/{track}', function (string $track) {
    $tracks = config('learning_tracks');
    abort_unless(isset($tracks[$track]), 404);
    return view('learning.track', ['slug' => $track, 'track' => $tracks[$track]]);
})->name('learning.track');
Route::get('/learn/{track}/{module}', function (string $track, string $module) {
    $tracks = config('learning_tracks');
    abort_unless(isset($tracks[$track]['topics'][$module]), 404);
    return view('learning.questions', ['trackSlug' => $track, 'track' => $tracks[$track], 'module' => $tracks[$track]['topics'][$module]]);
})->name('learning.show');
Route::get('/about', fn () => view('about-dynamic', [
    'stats' => ['jobs' => \App\Models\Job::active()->count(), 'companies' => \App\Models\Company::active()->whereHas('activeJobs')->count()],
    'technologies' => \App\Models\Technology::query()->orderBy('name')->pluck('name'),
]))->name('about');
Route::view('/practice', 'practice')->name('practice');
Route::middleware('guest')->group(function(){Route::get('/register',[AuthController::class,'registerForm'])->name('register');Route::post('/register',[AuthController::class,'register'])->name('register.store')->middleware('throttle:5,1');Route::get('/login',[AuthController::class,'loginForm'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store')->middleware('throttle:10,1');Route::get('/verify-otp',[AuthController::class,'otpForm'])->name('otp.form');Route::post('/verify-otp',[AuthController::class,'verify'])->name('otp.verify')->middleware('throttle:6,1');});
Route::view('/employers/register','auth.employer-register')->middleware('guest')->name('employer.register');
Route::middleware('auth')->group(function(){Route::view('/dashboard','dashboard')->name('dashboard');Route::post('/logout',[AuthController::class,'logout'])->name('logout');});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.')->group(function(){Route::get('/',[EmployerController::class,'dashboard'])->name('dashboard');Route::post('/company',[EmployerController::class,'company'])->name('company.store');Route::get('/jobs/create',[EmployerController::class,'create'])->name('jobs.create');Route::post('/jobs',[EmployerController::class,'store'])->name('jobs.store');Route::get('/jobs/{job}/edit',[EmployerController::class,'edit'])->name('jobs.edit');Route::put('/jobs/{job}',[EmployerController::class,'update'])->name('jobs.update');Route::patch('/jobs/{job}/close',[EmployerController::class,'close'])->name('jobs.close');});
Route::get('/admin',[AdminController::class,'index'])->middleware(['auth','admin'])->name('admin.dashboard');
Route::prefix('admin/jobs')->middleware(['auth','admin'])->group(function(){Route::patch('/{job}/publish',[AdminJobApprovalController::class,'publish'])->name('admin.jobs.publish');Route::patch('/{job}/reject',[AdminJobApprovalController::class,'reject'])->name('admin.jobs.reject');});
