<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\AdminJobApprovalController;
use App\Http\Controllers\CandidateJobController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\PracticeController;

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

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
Route::get('/jobs/{job:slug}',[CandidateJobController::class,'show'])->name('jobs.show');
Route::post('/jobs/{job:slug}/apply',[CandidateJobController::class,'apply'])->middleware('auth')->name('jobs.apply');

Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learn/{track}', [LearningController::class, 'track'])->name('learning.track');
Route::get('/learn/{track}/{module}', [LearningController::class, 'module'])->name('learning.show');
Route::get('/about', [AboutUsController::class, 'index'])->name('about');
Route::get('/contact', [ContactUsController::class, 'index'])->name('contact');
Route::get('/practice', [PracticeController::class, 'index'])->name('practice');
Route::middleware('guest')->group(function(){Route::get('/register',[AuthController::class,'registerForm'])->name('register');Route::post('/register',[AuthController::class,'register'])->name('register.store')->middleware('throttle:5,1');Route::get('/login',[AuthController::class,'loginForm'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store')->middleware('throttle:10,1');Route::get('/verify-otp',[AuthController::class,'otpForm'])->name('otp.form');Route::post('/verify-otp',[AuthController::class,'verify'])->name('otp.verify')->middleware('throttle:6,1');});
Route::view('/employers/register','auth.employer-register')->middleware('guest')->name('employer.register');
Route::get('/employers/login',[AuthController::class,'employerLoginForm'])->middleware('guest')->name('employer.login');
Route::get('/owner/login',[AuthController::class,'ownerLoginForm'])->middleware('guest')->name('owner.login');
Route::middleware('auth')->group(function(){Route::get('/dashboard',[CandidateProfileController::class,'dashboard'])->name('dashboard');Route::put('/profile',[CandidateProfileController::class,'update'])->name('candidate.profile.update');Route::post('/profile/resume',[CandidateProfileController::class,'uploadResume'])->name('candidate.resume.upload');Route::post('/logout',[AuthController::class,'logout'])->name('logout');});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.')->group(function(){Route::get('/',[EmployerController::class,'dashboard'])->name('dashboard');Route::post('/company',[EmployerController::class,'company'])->name('company.store');Route::get('/jobs/create',[EmployerController::class,'create'])->name('jobs.create');Route::post('/jobs',[EmployerController::class,'store'])->name('jobs.store');Route::get('/jobs/{job}/edit',[EmployerController::class,'edit'])->name('jobs.edit');Route::put('/jobs/{job}',[EmployerController::class,'update'])->name('jobs.update');Route::patch('/jobs/{job}/status/{status}',[EmployerController::class,'status'])->name('jobs.status');Route::delete('/jobs/{job}',[EmployerController::class,'destroy'])->name('jobs.destroy');Route::post('/jobs/{job}/duplicate',[EmployerController::class,'duplicate'])->name('jobs.duplicate');Route::get('/jobs/{job}/applicants',[EmployerController::class,'applicants'])->name('jobs.applicants');Route::patch('/applications/{application}/status',[EmployerController::class,'applicationStatus'])->name('applications.status');});
Route::get('/admin',[AdminController::class,'index'])->middleware(['auth','admin'])->name('admin.dashboard');
Route::prefix('admin/jobs')->middleware(['auth','admin'])->group(function(){Route::patch('/{job}/publish',[AdminJobApprovalController::class,'publish'])->name('admin.jobs.publish');Route::patch('/{job}/reject',[AdminJobApprovalController::class,'reject'])->name('admin.jobs.reject');});
