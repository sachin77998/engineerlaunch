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
Route::get('/sectors', [\App\Http\Controllers\SectorDirectoryController::class, 'index'])->name('sectors.index');
Route::get('/sectors/category/{group}', [\App\Http\Controllers\SectorDirectoryController::class, 'group'])->name('sectors.group');
Route::get('/sectors/{slug}', [\App\Http\Controllers\SectorDirectoryController::class, 'show'])->name('sectors.show');
Route::get('/sectors/{slug}/{sub}', [\App\Http\Controllers\SectorDirectoryController::class, 'subcategory'])->name('sectors.subcategory');
Route::get('/companies/category/{category:slug}', [CompanyDiscoveryController::class, 'index'])->name('companies.category');
Route::get('/companies/dlf-cyber-city', [CompanyDiscoveryController::class, 'cyberCity'])->name('companies.cyber-city');
Route::get('/companies/{company:slug}', [CompanyDiscoveryController::class, 'show'])->name('companies.show');
// Insights into Company: registry, financials and ratios from MCA filings.
Route::get('/company-insights', [\App\Http\Controllers\CompanyInsightsController::class, 'index'])->name('insights.index');
Route::get('/company-insights/explore/{db}', [\App\Http\Controllers\InsightsExploreController::class, 'show'])->where('db', 'listed|companies|us|people')->name('insights.explore');
// Do Trading: mutual funds (AMFI data), calculators, onboarding/KYC, orders. API endpoints answer JSON.
Route::prefix('trading')->name('trading.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Trading\TradingController::class, 'index'])->name('index');
    Route::get('/mutual-funds', [\App\Http\Controllers\Trading\TradingController::class, 'funds'])->name('funds');
    Route::get('/mutual-funds/collections/{collection}', [\App\Http\Controllers\Trading\TradingController::class, 'collection'])->name('collection');
    Route::get('/mutual-funds/fund-house/{amc}', [\App\Http\Controllers\Trading\TradingController::class, 'amc'])->name('amc');
    Route::get('/mutual-funds/category/{category:slug}', [\App\Http\Controllers\Trading\TradingController::class, 'category'])->name('category');
    Route::get('/stocks', [\App\Http\Controllers\Trading\MarketController::class, 'stocks'])->name('stocks');
    Route::get('/etfs', [\App\Http\Controllers\Trading\MarketController::class, 'etfs'])->name('etfs');
    Route::get('/markets/search', [\App\Http\Controllers\Trading\MarketController::class, 'search'])->middleware('throttle:trading-read')->name('markets.search');
    Route::get('/markets/charges', [\App\Http\Controllers\Trading\MarketController::class, 'charges'])->middleware('throttle:trading-read')->name('markets.charges');
    Route::get('/markets/{exchange}/{symbol}', [\App\Http\Controllers\Trading\MarketController::class, 'instrument'])->where(['exchange' => 'NSE|BSE|nse|bse', 'symbol' => '[A-Za-z0-9&_.-]+'])->name('instrument');
    Route::post('/broker/kite/postback', [\App\Http\Controllers\Trading\MarketController::class, 'postback'])->middleware('throttle:trading-read')->name('broker.postback');
    Route::middleware('auth')->group(function () {
        Route::get('/broker/kite/connect', [\App\Http\Controllers\Trading\MarketController::class, 'connect'])->name('broker.connect');
        Route::get('/broker/kite/callback', [\App\Http\Controllers\Trading\MarketController::class, 'callback'])->name('broker.callback');
        Route::post('/broker/kite/disconnect', [\App\Http\Controllers\Trading\MarketController::class, 'disconnect'])->name('broker.disconnect');
        Route::get('/broker/kite/ticker', [\App\Http\Controllers\Trading\MarketController::class, 'ticker'])->middleware('throttle:trading-read')->name('broker.ticker');
        Route::post('/markets/{exchange}/{symbol}/orders', [\App\Http\Controllers\Trading\MarketController::class, 'place'])->where(['exchange' => 'NSE|BSE|nse|bse', 'symbol' => '[A-Za-z0-9&_.-]+'])->middleware('throttle:trading-order')->name('stock-orders.place');
        Route::get('/stock-orders', [\App\Http\Controllers\Trading\MarketController::class, 'stockOrders'])->name('stock-orders');
        Route::get('/stock-orders/{order}', [\App\Http\Controllers\Trading\MarketController::class, 'showOrder'])->middleware('throttle:trading-read')->name('stock-orders.show');
        Route::post('/stock-orders/{order}/cancel', [\App\Http\Controllers\Trading\MarketController::class, 'cancelOrder'])->middleware('throttle:trading-otp')->name('stock-orders.cancel');
        Route::get('/loans', [\App\Http\Controllers\Trading\LoanController::class, 'index'])->name('loans');
        Route::post('/loans/preview', [\App\Http\Controllers\Trading\LoanController::class, 'preview'])->middleware('throttle:trading-read')->name('loans.preview');
        Route::post('/loans', [\App\Http\Controllers\Trading\LoanController::class, 'store'])->middleware('throttle:trading-kyc')->name('loans.store');
        Route::get('/loans/{loan}', [\App\Http\Controllers\Trading\LoanController::class, 'show'])->name('loans.show');
        Route::delete('/loans/{loan}', [\App\Http\Controllers\Trading\LoanController::class, 'destroy'])->name('loans.destroy');
        Route::get('/loans/{loan}/export/{format}', [\App\Http\Controllers\Trading\LoanController::class, 'export'])->middleware('throttle:trading-export')->name('loans.export');
    });
    Route::get('/funds/{scheme}', [\App\Http\Controllers\Trading\TradingController::class, 'fund'])->whereNumber('scheme')->name('fund');
    Route::get('/funds/{scheme}/history', [\App\Http\Controllers\Trading\TradingController::class, 'fundHistory'])->whereNumber('scheme')->middleware('throttle:60,1')->name('fund.history');
    Route::get('/calculators', [\App\Http\Controllers\Trading\TradingController::class, 'calculators'])->name('calculators');
    Route::middleware('throttle:trading-read')->prefix('api')->name('api.')->group(function () {
        Route::get('/fees', [\App\Http\Controllers\Trading\TradingApiController::class, 'fees'])->name('fees');
        Route::get('/funds', [\App\Http\Controllers\Trading\TradingApiController::class, 'funds'])->name('funds');
        Route::get('/funds/{scheme}/recommendations', [\App\Http\Controllers\Trading\TradingApiController::class, 'recommendations'])->whereNumber('scheme')->name('recommendations');
        Route::post('/calculators/{type}', [\App\Http\Controllers\Trading\TradingApiController::class, 'calculate'])->name('calculate');
        Route::get('/market', [\App\Http\Controllers\Trading\TradingApiController::class, 'market'])->name('market');
    });
    Route::middleware('auth')->group(function () {
        Route::get('/orders', [\App\Http\Controllers\Trading\TradingController::class, 'orders'])->name('orders');
        Route::get('/orders/export/{format}', [\App\Http\Controllers\Trading\TradingController::class, 'exportMine'])->middleware('throttle:trading-export')->name('orders.export');
        Route::get('/onboarding', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding/step/{step}', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'save'])->whereNumber('step')->middleware('throttle:trading-kyc')->name('onboarding.save');
        Route::post('/onboarding/minor-to-major', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'minorToMajor'])->middleware('throttle:trading-kyc')->name('onboarding.minor-major');
        Route::get('/onboarding/document/{document}', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'document'])->name('onboarding.document');
        Route::get('/onboarding/pincode', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'pincode'])->middleware('throttle:trading-kyc')->name('onboarding.pincode');
        Route::get('/onboarding/ifsc', [\App\Http\Controllers\Trading\TradingOnboardingController::class, 'ifsc'])->middleware('throttle:trading-kyc')->name('onboarding.ifsc');
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/orders', [\App\Http\Controllers\Trading\TradingApiController::class, 'index'])->middleware('throttle:trading-read')->name('orders.index');
            Route::post('/orders', [\App\Http\Controllers\Trading\TradingApiController::class, 'place'])->middleware('throttle:trading-order')->name('orders.place');
            Route::get('/orders/{order}', [\App\Http\Controllers\Trading\TradingApiController::class, 'show'])->middleware('throttle:trading-read')->name('orders.show');
            Route::post('/orders/{order}/verify', [\App\Http\Controllers\Trading\TradingApiController::class, 'verify'])->middleware('throttle:trading-otp')->name('orders.verify');
            Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Trading\TradingApiController::class, 'cancel'])->middleware('throttle:trading-otp')->name('orders.cancel');
            Route::patch('/orders/{order}/status', [\App\Http\Controllers\Trading\TradingApiController::class, 'updateStatus'])->middleware('throttle:trading-read')->name('orders.status');
        });
    });
    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Trading\TradingAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/clients/{client}', [\App\Http\Controllers\Trading\TradingAdminController::class, 'client'])->name('client');
        Route::post('/clients/{client}/check', [\App\Http\Controllers\Trading\TradingAdminController::class, 'verifyCheck'])->name('client.check');
        Route::post('/clients/{client}/decide', [\App\Http\Controllers\Trading\TradingAdminController::class, 'decide'])->name('client.decide');
        Route::post('/documents/{document}', [\App\Http\Controllers\Trading\TradingAdminController::class, 'reviewDocument'])->name('document');
        Route::post('/minor-major/{minorMajor}', [\App\Http\Controllers\Trading\TradingAdminController::class, 'minorMajor'])->name('minor-major');
        Route::get('/orders', [\App\Http\Controllers\Trading\TradingAdminController::class, 'orders'])->name('orders');
        Route::post('/orders/{order}', [\App\Http\Controllers\Trading\TradingAdminController::class, 'updateOrder'])->name('order');
        Route::get('/orders/export/{format}', [\App\Http\Controllers\Trading\TradingAdminController::class, 'export'])->name('orders.export');
        Route::get('/settings', [\App\Http\Controllers\Trading\TradingAdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\Trading\TradingAdminController::class, 'saveSettings'])->name('settings.save');
        Route::post('/holidays', [\App\Http\Controllers\Trading\TradingAdminController::class, 'addHoliday'])->name('holidays.add');
        Route::post('/sync', [\App\Http\Controllers\Trading\TradingAdminController::class, 'syncMarket'])->name('sync');
        Route::get('/audit', [\App\Http\Controllers\Trading\TradingAdminController::class, 'audit'])->name('audit');
    });
});
Route::get('/company-insights/cin/{cin}', [\App\Http\Controllers\CompanyInsightsController::class, 'byCin'])->where('cin', '[A-Za-z0-9]{21}')->name('insights.cin');
Route::get('/company-insights/{company:slug}/report.{format}', [\App\Http\Controllers\CompanyInsightsController::class, 'report'])->whereIn('format', ['pdf', 'xlsx'])->middleware(['auth', 'throttle:reports'])->name('insights.report');
Route::get('/company-insights/{company:slug}', [\App\Http\Controllers\CompanyInsightsController::class, 'show'])->name('insights.show');
Route::post('/company-insights/{company:slug}/feedback', [\App\Http\Controllers\CompanyInsightsController::class, 'feedback'])->middleware('throttle:30,1')->name('insights.feedback');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/company-insights/{company:slug}/filings', [\App\Http\Controllers\CompanyInsightsController::class, 'upload'])->name('insights.upload');
    Route::post('/company-insights/{company:slug}/statements', [\App\Http\Controllers\CompanyInsightsController::class, 'saveStatement'])->name('insights.statements');
    Route::post('/company-insights-instafinancials', [\App\Http\Controllers\CompanyInsightsController::class, 'importInsta'])->name('insights.insta');
    Route::post('/company-insights-master-data', [\App\Http\Controllers\CompanyInsightsController::class, 'masterData'])->name('insights.master-data');
});
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

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'email'])->middleware('throttle:password-email')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:password-update')->name('password.update');
});

Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
Route::get('/jobs/{job:slug}',[CandidateJobController::class,'show'])->name('jobs.show');
Route::get('/opportunities/{job}',[CandidateJobController::class,'show'])->whereNumber('job')->name('opportunities.show');
Route::post('/jobs/{job:slug}/apply',[CandidateJobController::class,'apply'])->middleware('auth')->name('jobs.apply');
Route::get('/my-applications',[\App\Http\Controllers\ApplicationTrackerController::class,'index'])->middleware('auth')->name('applications.track');
Route::patch('/applications/{application}/withdraw',[CandidateJobController::class,'withdraw'])->middleware('auth')->name('applications.withdraw');

Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
Route::get('/learn/{track}', [LearningController::class, 'track'])->name('learning.track');
Route::get('/learn/{track}/{module}', [LearningController::class, 'module'])->name('learning.show');
Route::post('/learning/quiz-answer',[LearningQuizController::class,'store'])->middleware(['auth','throttle:60,1'])->name('learning.quiz.store');
Route::get('/about', [AboutUsController::class, 'index'])->name('about');
Route::get('/contact', [ContactUsController::class, 'index'])->name('contact');
Route::get('/practice', [PracticeController::class, 'index'])->name('practice');

Route::get('/ai/chat', function () {return view('ai.chat');})->name('ai.chat');
Route::get('/market', [\App\Http\Controllers\MarketController::class, 'index'])->name('market.index');
// Financial calculators (gold/silver/copper with price trends, loans, investments, savings schemes, retirement, tax).
Route::get('/calculators', [\App\Http\Controllers\CalculatorController::class, 'index'])->name('calculators.index');
Route::get('/calculators/{slug}', [\App\Http\Controllers\CalculatorController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('calculators.show');
// Insurance advisor (health, term, car, bike): cover, estimated premium and fee structure, insurers ranked by public figures.
Route::get('/insurance/{product?}', [\App\Http\Controllers\InsuranceController::class, 'index'])->where('product', 'health|term|car|bike')->name('insurance.index');
// Money planner (free): retirement, tax saving, retirement income & insurance, fund picks with ML timing signals.
Route::prefix('money')->name('money.')->controller(\App\Http\Controllers\MoneyController::class)->group(function(){
    Route::get('/','index')->name('index');
    Route::post('/profile','save')->name('save');
    Route::get('/tax','tax')->name('tax');
    Route::get('/income','income')->name('income');
    Route::get('/funds','funds')->name('funds');
});
Route::post('/assistant/chat',[\App\Http\Controllers\AssistantChatController::class,'chat'])->middleware('throttle:assistant')->name('assistant.chat');
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/insurers', [\App\Http\Controllers\InsuranceController::class, 'adminIndex'])->name('admin.insurers');
    Route::patch('/admin/insurers/{insurer}', [\App\Http\Controllers\InsuranceController::class, 'adminUpdate'])->name('admin.insurers.update');
    Route::get('/admin/premium-orders', [\App\Http\Controllers\PremiumController::class, 'adminOrders'])->name('admin.premium.orders');
    Route::post('/admin/premium-orders/{order}/activate', [\App\Http\Controllers\PremiumController::class, 'adminActivate'])->name('admin.premium.activate');
});
// US stock market: screener of NYSE / Nasdaq stocks and price history per ticker.
Route::get('/markets/us', [\App\Http\Controllers\UsMarketController::class, 'index'])->name('markets.us');
Route::get('/markets/us/{ticker}/history', [\App\Http\Controllers\UsMarketController::class, 'history'])->where('ticker', '[A-Za-z0-9.\-]{1,16}')->middleware('throttle:60,1')->name('markets.us.history');
Route::get('/markets/nse/{symbol}/history', [\App\Http\Controllers\UsMarketController::class, 'nseHistory'])->where('symbol', '[A-Za-z0-9&\-]{1,20}')->middleware('throttle:60,1')->name('markets.nse.history');
// Premium plans: fee breakdown, checkout and Razorpay payment.
Route::get('/premium', [\App\Http\Controllers\PremiumController::class, 'index'])->name('premium.index');
Route::middleware('auth')->group(function () {
    Route::post('/premium/checkout', [\App\Http\Controllers\PremiumController::class, 'checkout'])->middleware('throttle:10,1')->name('premium.checkout');
    Route::get('/premium/orders/{orderNo}', [\App\Http\Controllers\PremiumController::class, 'pay'])->name('premium.pay');
    Route::post('/premium/orders/{orderNo}/verify', [\App\Http\Controllers\PremiumController::class, 'verify'])->middleware('throttle:20,1')->name('premium.verify');
});
Route::get('/admin/market/template/{type}', [\App\Http\Controllers\MarketController::class, 'template'])->middleware(['auth','admin'])->name('admin.market.template');
Route::post('/admin/market/upload', [\App\Http\Controllers\MarketController::class, 'upload'])->middleware(['auth','admin'])->name('admin.market.upload');
Route::get('/technologies', [\App\Http\Controllers\TechnologyController::class, 'index'])->name('technologies.index');
Route::get('/technologies/{slug}', [\App\Http\Controllers\TechnologyController::class, 'show'])->name('technologies.show');
Route::get('/recommendations/jobs', [\App\Http\Controllers\RecommendationController::class, 'jobs'])->middleware('throttle:60,1')->name('recommendations.jobs');
Route::post('/assistant/jobs', [\App\Http\Controllers\JobAssistantController::class, 'recommend'])->name('assistant.jobs');

Route::post('/practice/run', [PracticeController::class, 'run'])->middleware(['auth', 'throttle:20,1'])->name('practice.run');
Route::middleware('guest')->group(function(){Route::get('/register',[AuthController::class,'registerForm'])->name('register');Route::post('/register',[AuthController::class,'register'])->name('register.store')->middleware('throttle:signup');Route::get('/login',[AuthController::class,'loginForm'])->name('login');Route::post('/login',[AuthController::class,'login'])->name('login.store')->middleware('throttle:login');Route::get('/verify-otp',[AuthController::class,'otpForm'])->name('otp.form');Route::post('/verify-otp',[AuthController::class,'verify'])->name('otp.verify')->middleware('throttle:otp-verify');Route::post('/verify-otp/resend',[AuthController::class,'resendOtp'])->name('otp.resend')->middleware('throttle:otp-resend');});
Route::get('/employers/register',[EmployerRegistrationController::class,'create'])->middleware('guest')->name('employer.register');
Route::post('/employers/register',[EmployerRegistrationController::class,'store'])->middleware(['guest','throttle:signup'])->name('employer.register.store');
Route::post('/employers/phone-otp',[EmployerRegistrationController::class,'sendPhoneOtp'])->middleware(['guest','throttle:otp-resend'])->name('employer.phone.send');
Route::post('/employers/phone-otp/verify',[EmployerRegistrationController::class,'verifyPhoneOtp'])->middleware(['guest','throttle:otp-verify'])->name('employer.phone.verify');
Route::view('/students/login','auth.student-login')->middleware('guest')->name('student.login');
Route::get('/students/register',[AuthController::class,'registerForm'])->middleware('guest')->name('student.register');
Route::post('/students/register',[AuthController::class,'register'])->middleware(['guest','throttle:signup'])->name('student.register.store');
Route::get('/employers/login',[AuthController::class,'employerLoginForm'])->middleware('guest')->name('employer.login');
Route::get('/owner/login',[AuthController::class,'ownerLoginForm'])->middleware('guest')->name('owner.login');
Route::middleware('auth')->group(function(){Route::get('/dashboard',[CandidateProfileController::class,'dashboard'])->name('dashboard');Route::put('/profile',[CandidateProfileController::class,'update'])->name('candidate.profile.update');Route::patch('/profile/guidance',[CandidateProfileController::class,'updateGuidance'])->name('candidate.profile.guidance');Route::post('/profile/resume',[CandidateProfileController::class,'uploadResume'])->name('candidate.resume.upload');Route::post('/logout',[AuthController::class,'logout'])->name('logout');});
Route::middleware(['auth','throttle:60,1'])->prefix('resume')->name('resume.')->group(function(){Route::get('/builder',[AtsResumeController::class,'edit'])->name('builder');Route::post('/save',[AtsResumeController::class,'save'])->name('save');Route::get('/download',[AtsResumeController::class,'download'])->name('download');Route::get('/lookups/companies',[AtsResumeController::class,'companies'])->name('companies');Route::get('/lookups/locations',[AtsResumeController::class,'locations'])->name('locations');Route::get('/lookups/institutions',[AtsResumeController::class,'institutions'])->name('institutions');});
Route::post('/profile/auto-apply',[CandidateAutoApplyController::class,'update'])->middleware(['auth','throttle:10,1'])->name('candidate.auto-apply.update');
Route::middleware(['auth','throttle:30,1'])->prefix('profile')->group(function(){Route::get('/setup',[CandidateAssistantController::class,'setup'])->name('candidate.setup');Route::get('/assistant',[CandidateAssistantController::class,'show'])->name('candidate.assistant');Route::post('/assistant/answer',[CandidateAssistantController::class,'answer'])->name('candidate.assistant.answer');Route::post('/assistant/back',[CandidateAssistantController::class,'back'])->name('candidate.assistant.back');Route::post('/assistant/complete',[CandidateAssistantController::class,'complete'])->name('candidate.assistant.complete');Route::post('/assistant/reset',[CandidateAssistantController::class,'reset'])->name('candidate.assistant.reset');});
Route::middleware('auth')->prefix('profile/lookups')->group(function(){Route::get('/states',[ProfileLookupController::class,'states'])->name('profile.lookups.states');Route::get('/cities',[ProfileLookupController::class,'cities'])->name('profile.lookups.cities');Route::get('/companies',[ProfileLookupController::class,'companies'])->name('profile.lookups.companies');Route::get('/institutions',[ProfileLookupController::class,'institutions'])->name('profile.lookups.institutions');});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.')->group(function(){Route::get('/',[EmployerController::class,'dashboard'])->name('dashboard');Route::post('/company',[EmployerController::class,'company'])->name('company.store');Route::get('/jobs/create',[EmployerController::class,'create'])->name('jobs.create');Route::get('/assistant/{flow}',[\App\Http\Controllers\EmployerAssistantController::class,'show'])->whereIn('flow',['company','job'])->name('assistant');Route::post('/assistant/{flow}/answer',[\App\Http\Controllers\EmployerAssistantController::class,'answer'])->whereIn('flow',['company','job'])->name('assistant.answer');Route::post('/assistant/{flow}/back',[\App\Http\Controllers\EmployerAssistantController::class,'back'])->whereIn('flow',['company','job'])->name('assistant.back');Route::post('/assistant/{flow}/reset',[\App\Http\Controllers\EmployerAssistantController::class,'reset'])->whereIn('flow',['company','job'])->name('assistant.reset');Route::post('/assistant/{flow}/complete',[\App\Http\Controllers\EmployerAssistantController::class,'complete'])->whereIn('flow',['company','job'])->name('assistant.complete');Route::post('/jobs',[EmployerController::class,'store'])->name('jobs.store');Route::get('/jobs/{job}/edit',[EmployerController::class,'edit'])->name('jobs.edit');Route::put('/jobs/{job}',[EmployerController::class,'update'])->name('jobs.update');Route::patch('/jobs/{job}/status/{status}',[EmployerController::class,'status'])->name('jobs.status');Route::delete('/jobs/{job}',[EmployerController::class,'destroy'])->name('jobs.destroy');Route::post('/jobs/{job}/duplicate',[EmployerController::class,'duplicate'])->name('jobs.duplicate');Route::get('/jobs/{job}/applicants',[EmployerController::class,'applicants'])->name('jobs.applicants');Route::patch('/applications/{application}/status',[EmployerController::class,'applicationStatus'])->name('applications.status');});
// Hiring suite: permanent talent database, pipelines with auto-screening, interviews, verification and hiring analytics.
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.hiring.')->controller(\App\Http\Controllers\HiringSuiteController::class)->group(function(){
    Route::get('/talent','talent')->name('talent');
    Route::get('/talent/export','export')->name('export');
    Route::post('/talent/searches','saveSearch')->name('searches.store');
    Route::delete('/talent/searches/{search}','deleteSearch')->name('searches.destroy');
    Route::post('/applications/bulk','bulk')->name('bulk');
    Route::get('/jobs/{job}/pipeline','pipeline')->name('pipeline');
    Route::post('/jobs/{job}/screening','saveRules')->name('rules');
    Route::get('/candidates/{application}','candidate')->name('candidate');
    Route::patch('/candidates/{application}/stage','stage')->name('stage');
    Route::patch('/candidates/{application}','updateCandidate')->name('update');
    Route::post('/candidates/{application}/notes','note')->name('note');
    Route::post('/candidates/{application}/interviews','schedule')->name('schedule');
    Route::get('/candidates/{application}/resume','resume')->name('resume');
    Route::post('/candidates/{application}/verify/pan','verifyPan')->name('verify.pan');
    Route::post('/candidates/{application}/verify/aadhaar','aadhaarStart')->name('verify.aadhaar');
    Route::post('/verifications/{verification}/otp','aadhaarFinish')->name('verify.otp');
    Route::patch('/interviews/{interview}','feedback')->name('feedback');
    Route::get('/interviews','interviews')->name('interviews');
    Route::get('/analytics','analytics')->name('analytics');
    Route::post('/analytics/costs','saveCosts')->name('costs');
});
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.hiring.')->group(function(){
    Route::controller(\App\Http\Controllers\HiringTeamController::class)->group(function(){
        Route::get('/team','index')->name('team');
        Route::post('/team','invite')->name('team.invite');
        Route::post('/team/leave','leave')->name('team.leave');
        Route::post('/team/{member}/resend','resend')->name('team.resend');
        Route::patch('/team/{member}','update')->name('team.update');
        Route::delete('/team/{member}','remove')->name('team.remove');
    });
    Route::controller(\App\Http\Controllers\HiringCommsController::class)->group(function(){
        Route::post('/candidates/{application}/email','email')->name('email');
        Route::post('/email-templates','saveTemplate')->name('templates.store');
        Route::delete('/email-templates/{template}','deleteTemplate')->name('templates.destroy');
        Route::post('/candidates/{application}/offers','storeOffer')->name('offers.store');
        Route::post('/offers/{offer}/send','sendOffer')->name('offers.send');
        Route::post('/offers/{offer}/withdraw','withdrawOffer')->name('offers.withdraw');
        Route::get('/offers/{offer}/pdf','offerPdf')->name('offers.pdf');
        Route::post('/candidates/{application}/verify/uan','verifyUan')->name('verify.uan');
    });
});
// Hiring automation (workflows) and online onboarding.
Route::prefix('employer')->middleware(['auth','employer'])->name('employer.hiring.')->group(function(){
    Route::controller(\App\Http\Controllers\HiringWorkflowController::class)->group(function(){
        Route::get('/workflows','index')->name('workflows');
        Route::post('/workflows','store')->name('workflows.store');
        Route::put('/workflows/{workflow}','update')->name('workflows.update');
        Route::patch('/workflows/{workflow}/toggle','toggle')->name('workflows.toggle');
        Route::delete('/workflows/{workflow}','destroy')->name('workflows.destroy');
    });
    Route::controller(\App\Http\Controllers\HiringOnboardingController::class)->group(function(){
        Route::get('/onboarding','index')->name('onboarding');
        Route::get('/onboarding/{onboarding}','show')->name('onboarding.show');
        Route::post('/candidates/{application}/onboarding','start')->name('onboarding.start');
        Route::post('/onboarding/{onboarding}/resend','resend')->name('onboarding.resend');
        Route::post('/onboarding/{onboarding}/cancel','cancel')->name('onboarding.cancel');
        Route::patch('/onboarding-items/{item}','review')->name('onboarding.review');
        Route::get('/onboarding-items/{item}/file','file')->name('onboarding.file');
        Route::post('/onboarding-templates','saveTemplate')->name('onboarding.templates.store');
        Route::delete('/onboarding-templates/{template}','deleteTemplate')->name('onboarding.templates.destroy');
    });
});
Route::get('/joining/{token}',[\App\Http\Controllers\HiringOnboardingController::class,'publicShow'])->where('token','[A-Za-z0-9]{48}')->name('hiring.onboarding.public');
Route::post('/joining/{token}/items/{item}',[\App\Http\Controllers\HiringOnboardingController::class,'publicUpload'])->where('token','[A-Za-z0-9]{48}')->middleware('throttle:onboarding-upload')->name('hiring.onboarding.upload');
// Public links sent by email: team invites and candidate offer responses (token-protected).
Route::get('/hiring/invite/{token}',[\App\Http\Controllers\HiringTeamController::class,'show'])->where('token','[A-Za-z0-9]{48}')->name('hiring.invite.show');
Route::post('/hiring/invite/{token}',[\App\Http\Controllers\HiringTeamController::class,'register'])->where('token','[A-Za-z0-9]{48}')->middleware('guest')->name('hiring.invite.register');
Route::get('/offer/{token}',[\App\Http\Controllers\HiringCommsController::class,'showPublic'])->where('token','[A-Za-z0-9]{48}')->name('hiring.offer.show');
Route::get('/offer/{token}/pdf',[\App\Http\Controllers\HiringCommsController::class,'pdfPublic'])->where('token','[A-Za-z0-9]{48}')->name('hiring.offer.pdf');
Route::post('/offer/{token}',[\App\Http\Controllers\HiringCommsController::class,'respondPublic'])->where('token','[A-Za-z0-9]{48}')->name('hiring.offer.respond');
Route::get('/admin',[AdminController::class,'index'])->middleware(['auth','admin'])->name('admin.dashboard');
Route::get('/admin/analytics',OwnerAnalyticsDashboardController::class)->middleware(['auth','admin'])->name('admin.analytics');
Route::middleware(['auth','admin'])->prefix('admin/money-rules')->name('admin.money-rules.')->controller(\App\Http\Controllers\AdminMoneyRulesController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/settings', 'settings')->name('settings');
    Route::post('/table/{table}', 'table')->where('table', 'money_[a-z_]+')->name('table');
    Route::post('/reset', 'reset')->name('reset');
});
Route::middleware(['auth','admin'])->prefix('admin/methodology')->name('admin.methodology.')->controller(\App\Http\Controllers\AdminMethodologyController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/ratios', 'ratios')->name('ratios');
    Route::post('/settings', 'settings')->name('settings');
    Route::post('/limits', 'limits')->name('limits');
    Route::post('/sectors/{id}', 'sector')->whereNumber('id')->name('sector');
    Route::post('/agency/{id}', 'agency')->whereNumber('id')->name('agency');
    Route::post('/recompute', 'recompute')->name('recompute');
    Route::post('/reset', 'reset')->name('reset');
});
Route::get('/admin/visitors',[\App\Http\Controllers\AdminVisitorsController::class,'index'])->middleware(['auth','admin'])->name('admin.visitors');
Route::get('/admin/visitors/export',[\App\Http\Controllers\AdminVisitorsController::class,'export'])->middleware(['auth','admin','throttle:reports'])->name('admin.visitors.export');
Route::view('/privacy','privacy')->name('privacy');
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

// State single-window approvals: licences, NOCs and connections with their document checklists, policies and acts.
Route::prefix('state-approvals')->name('approvals.')->controller(\App\Http\Controllers\StateApprovalController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{stateSlug}', 'state')->name('state');
    Route::get('/{stateSlug}/{externalId}', 'service')->where('externalId', '[A-Za-z0-9]+-[A-Za-z0-9]+')->name('service');
});
Route::middleware(['auth', 'admin'])->prefix('admin/state-approvals')->name('admin.approvals.')->controller(\App\Http\Controllers\AdminStateApprovalController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::put('/sources/{state}', 'saveSource')->whereNumber('state')->name('source');
    Route::post('/sources/{state}/fetch', 'fetch')->whereNumber('state')->name('fetch');
    Route::get('/states/{state}/services', 'services')->whereNumber('state')->name('services');
    Route::get('/services/{service}', 'edit')->whereNumber('service')->name('edit');
    Route::put('/services/{service}', 'update')->whereNumber('service')->name('update');
});

Route::get("/career-explorer", [\App\Http\Controllers\CareerExplorerController::class,"index"])->name("career-explorer");
Route::get("/api/career-explorer", [\App\Http\Controllers\CareerExplorerController::class,"api"])->middleware("throttle:30,1")->name("career-explorer.api");

Route::get('/admin/maintenance', [\App\Http\Controllers\AdminMaintenanceController::class, 'index'])->middleware(['auth','admin'])->name('admin.maintenance');
Route::post('/admin/maintenance/repository/{index}/{action}', [\App\Http\Controllers\AdminMaintenanceController::class, 'repository'])->whereNumber('index')->whereIn('action',['clean','deploy'])->middleware(['auth','admin'])->name('admin.maintenance.repository');
Route::post('/admin/maintenance/queue/{step}', [\App\Http\Controllers\AdminMaintenanceController::class, 'queue'])->middleware(['auth','admin'])->name('admin.maintenance.queue');
Route::post('/admin/maintenance/refresh-all', [\App\Http\Controllers\AdminMaintenanceController::class, 'refreshAll'])->middleware(['auth','admin'])->name('admin.maintenance.refresh-all');
Route::get('/admin/maintenance/status', [\App\Http\Controllers\AdminMaintenanceController::class, 'status'])->middleware(['auth','admin'])->name('admin.maintenance.status');
Route::post('/admin/maintenance/run/{step}', [\App\Http\Controllers\AdminMaintenanceController::class, 'run'])->middleware(['auth','admin'])->name('admin.maintenance.run');
Route::get('/admin/deploy-log', \App\Http\Controllers\AdminDeployLogController::class)->middleware(['auth','admin'])->name('admin.deploy-log');
Route::get('/admin/job-sources', [\App\Http\Controllers\AdminJobSourcesController::class,'index'])->middleware(['auth','admin'])->name('admin.job-sources');
Route::post('/admin/job-sources/refresh', [\App\Http\Controllers\AdminJobSourcesController::class,'refresh'])->middleware(['auth','admin','throttle:2,1,job-source-refresh:'])->name('admin.job-sources.refresh');

require __DIR__.'/insights-vendors.php';
