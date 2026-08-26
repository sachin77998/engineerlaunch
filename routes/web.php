<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EmployerController;
use App\Http\Controllers\AdminJobApprovalController;
use App\Http\Controllers\CandidateJobController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\CandidateAssistantController;
use App\Http\Controllers\ProfileLookupController;
use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\PracticeController;
use App\Http\Controllers\OwnerAnalyticsDashboardController;
use App\Http\Controllers\AtsResumeController;
use App\Http\Controllers\EmployerRegistrationController;
use App\Http\Controllers\HrJobListingController;
use App\Http\Controllers\LearningQuizController;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/jobs-posted-by-hr',HrJobListingController::class)->name('jobs.hr');

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
Route::get('/jobs/{job:slug}',[CandidateJobController::class,'show'])->name('jobs.show');
Route::post('/jobs/{job:slug}/apply',[CandidateJobController::class,'apply'])->middleware('auth')->name('jobs.apply');
Route::patch('/applications/{application}/withdraw',[CandidateJobController::class,'withdraw'])->middleware('auth')->name('applications.withdraw');

Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learn/{track}', [LearningController::class, 'track'])->name('learning.track');
Route::get('/learn/{track}/{module}', [LearningController::class, 'module'])->name('learning.show');
Route::post('/learning/quiz-answer',[LearningQuizController::class,'store'])->middleware(['auth','throttle:60,1'])->name('learning.quiz.store');
Route::get('/about', [AboutUsController::class, 'index'])->name('about');
Route::get('/contact', [ContactUsController::class, 'index'])->name('contact');
Route::get('/practice', [PracticeController::class, 'index'])->name('practice');
Route::middleware('guest')->group(function(){Route::get('/register',[AuthController::class,'registerForm'])->name('register');Route::post('/register',[AuthController::class,'register'])->name('register.store')->middleware('throttle:5,1');Route::get('/login',[AuthController::class,'loginForm'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store')->middleware('throttle:10,1');Route::get('/verify-otp',[AuthController::class,'otpForm'])->name('otp.form');Route::post('/verify-otp',[AuthController::class,'verify'])->name('otp.verify')->middleware('throttle:6,1');});
Route::get('/employers/register',[EmployerRegistrationController::class,'create'])->middleware('guest')->name('employer.register');
Route::post('/employers/register',[EmployerRegistrationController::class,'store'])->middleware(['guest','throttle:5,1'])->name('employer.register.store');
Route::post('/employers/phone-otp',[EmployerRegistrationController::class,'sendPhoneOtp'])->middleware(['guest','throttle:4,1'])->name('employer.phone.send');
Route::post('/employers/phone-otp/verify',[EmployerRegistrationController::class,'verifyPhoneOtp'])->middleware(['guest','throttle:8,1'])->name('employer.phone.verify');
Route::view('/students/login','auth.student-login')->middleware('guest')->name('student.login');
Route::get('/students/register',[AuthController::class,'registerForm'])->middleware('guest')->name('student.register');
Route::post('/students/register',[AuthController::class,'register'])->middleware(['guest','throttle:5,1'])->name('student.register.store');
Route::get('/employers/login',[AuthController::class,'employerLoginForm'])->middleware('guest')->name('employer.login');
Route::get('/owner/login',[AuthController::class,'ownerLoginForm'])->middleware('guest')->name('owner.login');
Route::get('/owner/register',[AuthController::class,'ownerRegisterForm'])->name('owner.register');
Route::post('/owner/register',[AuthController::class,'ownerRegister'])->middleware('throttle:5,1')->name('owner.register.store');
Route::middleware('auth')->group(function(){Route::get('/dashboard',[CandidateProfileController::class,'dashboard'])->name('dashboard');Route::put('/profile',[CandidateProfileController::class,'update'])->name('candidate.profile.update');Route::post('/profile/resume',[CandidateProfileController::class,'uploadResume'])->name('candidate.resume.upload');Route::post('/logout',[AuthController::class,'logout'])->name('logout');});
Route::middleware(['auth','throttle:60,1'])->prefix('resume')->name('resume.')->group(function(){Route::get('/builder',[AtsResumeController::class,'edit'])->name('builder');Route::post('/save',[AtsResumeController::class,'save'])->name('save');Route::get('/download',[AtsResumeController::class,'download'])->name('download');});
Route::middleware(['auth','throttle:30,1'])->prefix('profile')->group(function(){Route::get('/setup',[CandidateAssistantController::class,'setup'])->name('candidate.setup');Route::get('/assistant',[CandidateAssistantController::class,'show'])->name('candidate.assistant');Route::post('/assistant/answer',[CandidateAssistantController::class,'answer'])->name('candidate.assistant.answer');Route::post('/assistant/back',[CandidateAssistantController::class,'back'])->name('candidate.assistant.back');Route::post('/assistant/complete',[CandidateAssistantController::class,'complete'])->name('candidate.assistant.complete');Route::post('/assistant/reset',[CandidateAssistantController::class,'reset'])->name('candidate.assistant.reset');});
Route::middleware('auth')->prefix('profile/lookups')->group(function(){Route::get('/states',[ProfileLookupController::class,'states'])->name('profile.lookups.states');Route::get('/cities',[ProfileLookupController::class,'cities'])->name('profile.lookups.cities');Route::get('/companies',[ProfileLookupController::class,'companies'])->name('profile.lookups.companies');});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.')->group(function(){Route::get('/',[EmployerController::class,'dashboard'])->name('dashboard');Route::post('/company',[EmployerController::class,'company'])->name('company.store');Route::get('/jobs/create',[EmployerController::class,'create'])->name('jobs.create');Route::post('/jobs',[EmployerController::class,'store'])->name('jobs.store');Route::get('/jobs/{job}/edit',[EmployerController::class,'edit'])->name('jobs.edit');Route::put('/jobs/{job}',[EmployerController::class,'update'])->name('jobs.update');Route::patch('/jobs/{job}/status/{status}',[EmployerController::class,'status'])->name('jobs.status');Route::delete('/jobs/{job}',[EmployerController::class,'destroy'])->name('jobs.destroy');Route::post('/jobs/{job}/duplicate',[EmployerController::class,'duplicate'])->name('jobs.duplicate');Route::get('/jobs/{job}/applicants',[EmployerController::class,'applicants'])->name('jobs.applicants');Route::patch('/applications/{application}/status',[EmployerController::class,'applicationStatus'])->name('applications.status');});
Route::get('/admin',[AdminController::class,'index'])->middleware(['auth','admin'])->name('admin.dashboard');
Route::get('/admin/analytics',OwnerAnalyticsDashboardController::class)->middleware(['auth','admin'])->name('admin.analytics');
Route::prefix('admin/jobs')->middleware(['auth','admin'])->group(function(){Route::patch('/{job}/publish',[AdminJobApprovalController::class,'publish'])->name('admin.jobs.publish');Route::patch('/{job}/reject',[AdminJobApprovalController::class,'reject'])->name('admin.jobs.reject');});
