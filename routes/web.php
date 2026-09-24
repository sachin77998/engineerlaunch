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
use App\Http\Controllers\CompanyDiscoveryController;
use App\Http\Controllers\CandidateAutoApplyController;
use App\Http\Controllers\CompanyReviewController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\CompanyExperienceController;
use App\Http\Controllers\AdminCompanyExperienceController;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/latest', [NewsController::class, 'index'])->name('news.index');
Route::get('/latest/ajax', [NewsController::class, 'ajax'])->name('news.ajax');
Route::get('/latest/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::get('/companies', [CompanyDiscoveryController::class, 'index'])->name('companies.index');
Route::get('/companies/category/{category:slug}', [CompanyDiscoveryController::class, 'index'])->name('companies.category');
Route::get('/companies/{company:slug}', [CompanyDiscoveryController::class, 'show'])->name('companies.show');
Route::post('/companies/{company:slug}/reviews', [CompanyReviewController::class, 'store'])->middleware(['auth','throttle:6,1'])->name('companies.reviews.store');
Route::get('/jobs-posted-by-hr',HrJobListingController::class)->name('jobs.hr');
Route::prefix('company-experiences')->name('company.experiences.')->group(function(){
 Route::get('/',[CompanyExperienceController::class,'index'])->name('index');
 Route::get('/compare',[CompanyExperienceController::class,'compare'])->name('compare');
 Route::get('/share',[CompanyExperienceController::class,'create'])->middleware('auth')->name('create');
 Route::post('/share',[CompanyExperienceController::class,'store'])->middleware(['auth','throttle:5,1'])->name('store');
 Route::get('/{companySlug}/experience/{experienceId}',[CompanyExperienceController::class,'experience'])->whereNumber('experienceId')->name('experience');
 Route::get('/{slug}',[CompanyExperienceController::class,'company'])->name('company');
});

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
Route::get('/jobs/{job:slug}',[CandidateJobController::class,'show'])->name('jobs.show');
Route::get('/opportunities/{job}',[CandidateJobController::class,'show'])->whereNumber('job')->name('opportunities.show');
Route::post('/jobs/{job:slug}/apply',[CandidateJobController::class,'apply'])->middleware('auth')->name('jobs.apply');
Route::patch('/applications/{application}/withdraw',[CandidateJobController::class,'withdraw'])->middleware('auth')->name('applications.withdraw');

Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learn/{track}', [LearningController::class, 'track'])->name('learning.track');
Route::get('/learn/{track}/{module}', [LearningController::class, 'module'])->name('learning.show');
Route::post('/learning/quiz-answer',[LearningQuizController::class,'store'])->middleware(['auth','throttle:60,1'])->name('learning.quiz.store');
Route::get('/about', [AboutUsController::class, 'index'])->name('about');
Route::get('/contact', [ContactUsController::class, 'index'])->name('contact');
Route::get('/practice', [PracticeController::class, 'index'])->name('practice');

Route::get('/ai/chat', function () {return view('ai.chat');})->name('ai.chat');

Route::post('/practice/run', [PracticeController::class, 'run'])->middleware(['auth', 'throttle:20,1'])->name('practice.run');
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
Route::middleware('auth')->group(function(){Route::get('/dashboard',[CandidateProfileController::class,'dashboard'])->name('dashboard');Route::put('/profile',[CandidateProfileController::class,'update'])->name('candidate.profile.update');Route::patch('/profile/guidance',[CandidateProfileController::class,'updateGuidance'])->name('candidate.profile.guidance');Route::post('/profile/resume',[CandidateProfileController::class,'uploadResume'])->name('candidate.resume.upload');Route::post('/logout',[AuthController::class,'logout'])->name('logout');});
Route::middleware(['auth','throttle:60,1'])->prefix('resume')->name('resume.')->group(function(){Route::get('/builder',[AtsResumeController::class,'edit'])->name('builder');Route::post('/save',[AtsResumeController::class,'save'])->name('save');Route::get('/download',[AtsResumeController::class,'download'])->name('download');Route::get('/lookups/companies',[AtsResumeController::class,'companies'])->name('companies');Route::get('/lookups/locations',[AtsResumeController::class,'locations'])->name('locations');Route::get('/lookups/institutions',[AtsResumeController::class,'institutions'])->name('institutions');});
Route::post('/profile/auto-apply',[CandidateAutoApplyController::class,'update'])->middleware(['auth','throttle:10,1'])->name('candidate.auto-apply.update');
Route::middleware(['auth','throttle:30,1'])->prefix('profile')->group(function(){Route::get('/setup',[CandidateAssistantController::class,'setup'])->name('candidate.setup');Route::get('/assistant',[CandidateAssistantController::class,'show'])->name('candidate.assistant');Route::post('/assistant/answer',[CandidateAssistantController::class,'answer'])->name('candidate.assistant.answer');Route::post('/assistant/back',[CandidateAssistantController::class,'back'])->name('candidate.assistant.back');Route::post('/assistant/complete',[CandidateAssistantController::class,'complete'])->name('candidate.assistant.complete');Route::post('/assistant/reset',[CandidateAssistantController::class,'reset'])->name('candidate.assistant.reset');});
Route::middleware('auth')->prefix('profile/lookups')->group(function(){Route::get('/states',[ProfileLookupController::class,'states'])->name('profile.lookups.states');Route::get('/cities',[ProfileLookupController::class,'cities'])->name('profile.lookups.cities');Route::get('/companies',[ProfileLookupController::class,'companies'])->name('profile.lookups.companies');Route::get('/institutions',[ProfileLookupController::class,'institutions'])->name('profile.lookups.institutions');});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.')->group(function(){Route::get('/',[EmployerController::class,'dashboard'])->name('dashboard');Route::post('/company',[EmployerController::class,'company'])->name('company.store');Route::get('/jobs/create',[EmployerController::class,'create'])->name('jobs.create');Route::post('/jobs',[EmployerController::class,'store'])->name('jobs.store');Route::get('/jobs/{job}/edit',[EmployerController::class,'edit'])->name('jobs.edit');Route::put('/jobs/{job}',[EmployerController::class,'update'])->name('jobs.update');Route::patch('/jobs/{job}/status/{status}',[EmployerController::class,'status'])->name('jobs.status');Route::delete('/jobs/{job}',[EmployerController::class,'destroy'])->name('jobs.destroy');Route::post('/jobs/{job}/duplicate',[EmployerController::class,'duplicate'])->name('jobs.duplicate');Route::get('/jobs/{job}/applicants',[EmployerController::class,'applicants'])->name('jobs.applicants');Route::patch('/applications/{application}/status',[EmployerController::class,'applicationStatus'])->name('applications.status');});
Route::get('/admin',[AdminController::class,'index'])->middleware(['auth','admin'])->name('admin.dashboard');
Route::get('/admin/analytics',OwnerAnalyticsDashboardController::class)->middleware(['auth','admin'])->name('admin.analytics');
Route::get('/admin/news',[AdminNewsController::class,'index'])->middleware(['auth','admin'])->name('admin.news');
Route::post('/admin/news/sources',[AdminNewsController::class,'source'])->middleware(['auth','admin','throttle:10,1'])->name('admin.news.sources');
Route::patch('/admin/news/{article}',[AdminNewsController::class,'moderate'])->middleware(['auth','admin'])->name('admin.news.moderate');
Route::get('/admin/company-experiences',[AdminCompanyExperienceController::class,'index'])->middleware(['auth','admin'])->name('admin.company-experiences.index');
Route::patch('/admin/company-experiences/{experience}',[AdminCompanyExperienceController::class,'moderate'])->middleware(['auth','admin','throttle:30,1'])->name('admin.company-experiences.moderate');
Route::prefix('admin/jobs')->middleware(['auth','admin'])->group(function(){Route::patch('/{job}/publish',[AdminJobApprovalController::class,'publish'])->name('admin.jobs.publish');Route::patch('/{job}/reject',[AdminJobApprovalController::class,'reject'])->name('admin.jobs.reject');});


Route::prefix('industrial-areas')->name('industrial.')->group(function () {
    Route::get('/ajax/cities', [\App\Http\Controllers\IndustrialAreaController::class, 'cities'])->name('ajax.cities');
    Route::get('/ajax/areas', [\App\Http\Controllers\IndustrialAreaController::class, 'areas'])->name('ajax.areas');
    Route::get('/ajax/companies/{area}', [\App\Http\Controllers\IndustrialAreaController::class, 'companies'])->whereNumber('area')->name('ajax.companies');
    Route::get('/ajax', [\App\Http\Controllers\IndustrialAreaController::class, 'ajax'])->name('ajax');
    Route::get('/', [\App\Http\Controllers\IndustrialAreaController::class, 'index'])->name('index');
    Route::get('/openings/{job}', [\App\Http\Controllers\IndustrialAreaController::class, 'opening'])->whereNumber('job')->name('jobs.show');
    Route::get('/state/{stateSlug}/{areaSlug}/company/{companySlug}', [\App\Http\Controllers\IndustrialAreaController::class, 'company'])->name('company');
    Route::get('/state/{stateSlug}/{areaSlug}', [\App\Http\Controllers\IndustrialAreaController::class, 'show'])->name('show');
    Route::get('/company/{companySlug}', [\App\Http\Controllers\IndustrialAreaController::class, 'legacyCompany'])->name('company.short');
    Route::get('/{areaSlug}', [\App\Http\Controllers\IndustrialAreaController::class, 'legacyArea'])->name('show.short');
});


Route::prefix('admin/industrial-areas')->middleware(['auth', 'admin'])->name('admin.industrial.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'index'])->name('index');
    Route::get('/cities', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'cities'])->name('cities');
    Route::patch('/cities', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'updateCity'])->name('cities.update');
    Route::get('/sources', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'sources'])->name('sources');
    Route::get('/lookups', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'lookups'])->name('lookups');
    Route::post('/import', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'import'])->middleware('throttle:10,1')->name('import');
    Route::get('/{type}/create', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'form'])->name('create');
    Route::post('/{type}', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'save'])->name('store');
    Route::get('/{type}/{record}/edit', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'form'])->whereNumber('record')->name('edit');
    Route::put('/{type}/{record}', [\App\Http\Controllers\AdminIndustrialAreaController::class, 'save'])->whereNumber('record')->name('update');
});
