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
// Search engines: sitemaps and robots.txt (built from APP_URL so they point at the live domain).
// Bills & Payments: categories, billers and municipal services by city.
Route::get('/bills', [\App\Http\Controllers\BillPaymentController::class, 'index'])->name('bills.index');
Route::get('/bills/city/{slug}', [\App\Http\Controllers\BillPaymentController::class, 'city'])->where('slug', '[a-z0-9-]+')->name('bills.city');
Route::get('/bills/{category}', [\App\Http\Controllers\BillPaymentController::class, 'category'])->where('category', '[a-z]+')->name('bills.category');
// Home Building: construction materials with live store prices, price lists, build-cost calculator, estimators and a material list.
Route::prefix('materials')->name('materials.')->controller(\App\Http\Controllers\MaterialsController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/search', 'search')->name('search');
    Route::get('/category/{slug}', 'category')->where('slug', '[a-z0-9-]+')->name('category');
    Route::get('/price-lists', 'priceLists')->name('price-lists');
    Route::get('/price-list/{slug}', 'priceList')->where('slug', '[a-z0-9-]+')->name('price-list');
    Route::get('/construction-cost-calculator', 'calculator')->name('calculator');
    Route::get('/estimator/{type}', 'estimator')->where('type', '[a-z]+')->name('estimator');
    Route::get('/pincode', 'pincode')->middleware('throttle:materials')->name('pincode');
    Route::get('/list', 'list')->name('list');
    Route::post('/list', 'listAdd')->middleware('throttle:materials')->name('list.add');
    Route::post('/list/update', 'listUpdate')->middleware('throttle:materials')->name('list.update');
    Route::post('/list/clear', 'listClear')->middleware('throttle:materials')->name('list.clear');
    Route::get('/list/export', 'listExport')->name('list.export');
    Route::get('/categories', 'categories')->name('categories');
    Route::get('/suggest', 'suggest')->middleware('throttle:materials')->name('suggest');
});
// Home Building store: checkout, orders (Paytm or pay on delivery), reviews, feedback; owner's back office.
Route::prefix('materials')->name('materials.')->controller(\App\Http\Controllers\MaterialShopController::class)->group(function () {
    Route::post('/feedback', 'feedback')->middleware('throttle:materials')->name('feedback');
    Route::middleware('auth')->group(function () {
        Route::get('/checkout', 'checkout')->name('checkout');
        Route::post('/checkout', 'place')->middleware('throttle:materials')->name('checkout.place');
        Route::get('/orders', 'orders')->name('orders');
        Route::get('/orders/{order:order_no}', 'order')->name('orders.show');
        Route::post('/orders/{order:order_no}/pay', 'pay')->middleware('throttle:20,1')->name('orders.pay');
        Route::post('/orders/{order:order_no}/cod', 'switchToCod')->middleware('throttle:materials')->name('orders.cod');
        Route::post('/orders/{order:order_no}/cancel', 'cancel')->middleware('throttle:materials')->name('orders.cancel');
        Route::post('/orders/{order:order_no}/reorder', 'reorder')->middleware('throttle:materials')->name('orders.reorder');
        Route::post('/{material:handle}/reviews', 'review')->where('material', '[A-Za-z0-9._-]+')->middleware('throttle:materials')->name('reviews.store');
    });
});
// Product pages last, so /materials/checkout, /materials/orders… reach their own routes.
Route::get('/materials/{material:handle}', [\App\Http\Controllers\MaterialsController::class, 'show'])->where('material', '[A-Za-z0-9._-]+')->name('materials.show');
Route::middleware(['auth', 'admin'])->prefix('admin/materials')->name('admin.materials.')->controller(\App\Http\Controllers\MaterialAdminController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/orders/{order:order_no}', 'updateOrder')->name('orders.update');
    Route::post('/feedback/{feedback}', 'replyFeedback')->name('feedback.update');
    Route::post('/reviews/{review}', 'moderateReview')->name('reviews.update');
    Route::post('/products/{material:handle}', 'updateProduct')->where('material', '[A-Za-z0-9._-]+')->name('products.update');
});
// Saved property searches with new-listing email alerts.
Route::controller(\App\Http\Controllers\SavedSearchController::class)->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/my-searches', 'index')->name('saved-searches.index');
        Route::post('/my-searches', 'store')->middleware('throttle:property-contact')->name('saved-searches.store');
        Route::post('/my-searches/{search}', 'update')->whereNumber('search')->name('saved-searches.update');
        Route::post('/my-searches/{search}/delete', 'destroy')->whereNumber('search')->name('saved-searches.destroy');
        Route::get('/my-searches/{search}/open', 'open')->whereNumber('search')->name('saved-searches.open');
    });
    Route::get('/my-searches/{search}/stop', 'unsubscribe')->whereNumber('search')->middleware('signed')->name('saved-searches.unsubscribe');
});
// Site visits (listings and projects) and the visitor's enquiries.
Route::middleware('auth')->controller(\App\Http\Controllers\SiteVisitController::class)->group(function () {
    Route::post('/visit/{type}/{slug}', 'book')->whereIn('type', ['property', 'project'])->middleware('throttle:property-contact')->name('site-visits.book');
    Route::get('/my-visits', 'index')->name('site-visits.index');
    Route::get('/my-visits/hosting', 'hosting')->name('site-visits.hosting');
    Route::post('/my-visits/{visit}/reschedule', 'reschedule')->whereNumber('visit')->middleware('throttle:property-contact')->name('site-visits.reschedule');
    Route::post('/my-visits/{visit}/cancel', 'cancel')->whereNumber('visit')->name('site-visits.cancel');
    Route::post('/my-visits/{visit}/host', 'host')->whereNumber('visit')->name('site-visits.host');
    Route::get('/my-enquiries', 'enquiries')->name('site-visits.enquiries');
    Route::post('/admin/projects/{project:id}/visits/{visit}', 'adminHost')->whereNumber('visit')->middleware('admin')->name('admin.projects.visit');
});
// City landing pages, projects (buildings) and builders.
Route::controller(\App\Http\Controllers\CityPageController::class)->group(function () {
    Route::get('/real-estate', 'index')->name('real-estate.index');
    Route::get('/real-estate/locate', 'locate')->middleware('throttle:property-lookup')->name('real-estate.locate');
    Route::get('/real-estate/searches', 'searches')->name('real-estate.searches');
    Route::post('/real-estate/searches/clear', 'clearSearches')->middleware('throttle:property-contact')->name('real-estate.searches.clear');
    Route::get('/real-estate/{city}', 'show')->where('city', '[a-z0-9-]+')->name('real-estate.city');
    Route::get('/real-estate/{city}/rate-locality', 'rate')->where('city', '[a-z0-9-]+')->name('real-estate.rate');
});
Route::controller(\App\Http\Controllers\ProjectController::class)->group(function () {
    Route::get('/projects/{city?}', 'index')->where('city', '[a-z0-9-]+')->name('projects.index');
    Route::get('/project/{project:slug}', 'show')->name('projects.show');
    Route::post('/project/{project:slug}/phone', 'phone')->middleware(['auth', 'throttle:property-contact'])->name('projects.phone');
    Route::post('/project/{project:slug}/enquire', 'enquire')->middleware('throttle:property-contact')->name('projects.enquire');
    Route::get('/project/{project:slug}/brochure', 'brochure')->middleware(['auth', 'throttle:property-contact'])->name('projects.brochure');
    Route::post('/project/{project:slug}/shortlist', 'shortlist')->middleware('auth')->name('projects.shortlist');
    Route::get('/builders/{builder:slug}', 'builder')->name('builders.show');
});

// Property marketplace: search (Buy / Rent / Commercial), listing pages, posting a free ad and My properties.
Route::controller(\App\Http\Controllers\PropertyController::class)->group(function () {
    Route::get('/properties', 'index')->name('properties.index');
    Route::get('/properties/suggest', 'suggest')->middleware('throttle:property-lookup')->name('properties.suggest');
    Route::get('/properties/geocode', 'geocode')->middleware(['auth', 'throttle:property-geo'])->name('properties.geocode');
    Route::get('/properties/shortlist', 'shortlisted')->middleware('auth')->name('properties.shortlist');
    Route::get('/properties/{mode}/{city?}', 'search')->where(['mode' => 'buy|rent|commercial-rent|commercial-buy', 'city' => '[a-z0-9-]+'])->name('properties.search');
    Route::get('/property/{property:slug}', 'show')->name('properties.show');
    Route::post('/property/{property:slug}/phone', 'phone')->middleware(['auth', 'throttle:property-contact'])->name('properties.phone');
    Route::post('/property/{property:slug}/message', 'message')->middleware('throttle:property-contact')->name('properties.message');
    Route::post('/property/{property:slug}/report', 'report')->middleware('throttle:property-contact')->name('properties.report');
    Route::post('/property/{property:slug}/shortlist', 'shortlist')->middleware('auth')->name('properties.shortlist.toggle');
});
// Locality reviews by residents.
Route::controller(\App\Http\Controllers\LocalityReviewController::class)->group(function () {
    Route::get('/localities/{city}/{locality}', 'show')->where(['city' => '[a-z0-9-]+', 'locality' => '[a-z0-9-]+'])->name('localities.show');
    Route::get('/localities/{city}/{locality}/review', 'create')->middleware('auth')->where(['city' => '[a-z0-9-]+', 'locality' => '[a-z0-9-]+'])->name('localities.review');
    Route::post('/localities/{city}/{locality}/review', 'store')->middleware(['auth', 'throttle:property-contact'])->where(['city' => '[a-z0-9-]+', 'locality' => '[a-z0-9-]+'])->name('localities.review.store');
    Route::post('/locality-reviews/{review}/vote', 'vote')->middleware(['auth', 'throttle:property-contact'])->name('localities.vote');
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/admin/locality-reviews', 'admin')->name('admin.localities.index');
        Route::post('/admin/locality-reviews/{review}', 'toggle')->name('admin.localities.toggle');
    });
});
Route::middleware('auth')->controller(\App\Http\Controllers\PropertyPostController::class)->group(function () {
    Route::get('/post-property', 'start')->name('properties.post');
    Route::post('/post-property/contact', 'saveOwner')->name('properties.owner');
    Route::post('/post-property', 'create')->name('properties.create');
    Route::get('/my-properties', 'mine')->name('properties.mine');
    Route::get('/my-properties/{id}/{step}', 'step')->whereNumber('id')->where('step', '(?!media$)[a-z_]+')->name('properties.step');
    Route::post('/my-properties/{id}/{step}', 'save')->whereNumber('id')->where('step', '(?!media$)[a-z_]+')->name('properties.save');
    Route::post('/my-properties/{id}/media', 'upload')->whereNumber('id')->middleware('throttle:property-upload')->name('properties.upload');
    Route::post('/my-properties/{id}/media/{media}', 'media')->whereNumber('id')->name('properties.media');
    Route::post('/my-properties/{id}/status/{action}', 'status')->whereNumber('id')->whereIn('action', ['close', 'reopen', 'delete'])->name('properties.status');
});
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/properties', [\App\Http\Controllers\PropertyAdminController::class, 'index'])->name('admin.properties.index');
    Route::post('/admin/properties/owners/{owner}/feature', [\App\Http\Controllers\PropertyAdminController::class, 'feature'])->name('admin.properties.feature');
    Route::post('/admin/properties/{property:id}/reports', [\App\Http\Controllers\PropertyAdminController::class, 'resolveReports'])->name('admin.properties.reports');
    Route::post('/admin/properties/{property:id}/verify', [\App\Http\Controllers\PropertyAdminController::class, 'verify'])->name('admin.properties.verify');
    Route::post('/admin/properties/{property:id}', [\App\Http\Controllers\PropertyAdminController::class, 'status'])->name('admin.properties.status');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\PropertyCatalogAdminController::class)->group(function () {
    Route::get('/admin/property-options', 'options')->name('admin.property-options.index');
    Route::post('/admin/property-options/{group}', 'saveOptions')->where('group', '[a-z0-9_.-]+')->name('admin.property-options.save');
    Route::get('/admin/property-cities', 'cities')->name('admin.property-cities.index');
    Route::post('/admin/property-cities', 'saveCity')->name('admin.property-cities.store');
    Route::post('/admin/property-cities/{city}', 'saveCity')->whereNumber('city')->name('admin.property-cities.update');
    Route::post('/admin/property-cities/{city}/localities', 'saveLocalities')->whereNumber('city')->name('admin.property-cities.localities');
    Route::get('/admin/builders', 'builders')->name('admin.builders.index');
    Route::post('/admin/builders', 'saveBuilder')->name('admin.builders.store');
    Route::post('/admin/builders/{builder:id}', 'saveBuilder')->name('admin.builders.update');
    Route::get('/admin/projects', 'projects')->name('admin.projects.index');
    Route::get('/admin/projects/new', 'editProject')->name('admin.projects.create');
    Route::post('/admin/projects', 'saveProject')->name('admin.projects.store');
    Route::get('/admin/projects/{project:id}', 'editProject')->name('admin.projects.edit');
    Route::post('/admin/projects/{project:id}', 'saveProject')->name('admin.projects.update');
    Route::post('/admin/projects/{project:id}/images', 'uploadImages')->middleware('throttle:property-upload')->name('admin.projects.images');
    Route::post('/admin/projects/{project:id}/images/{image}', 'imageAction')->whereNumber('image')->name('admin.projects.image');
    Route::post('/admin/projects/{project:id}/offers', 'saveOffer')->name('admin.projects.offers');
    Route::post('/admin/projects/{project:id}/offers/{offer}', 'saveOffer')->whereNumber('offer')->name('admin.projects.offer');
    Route::post('/admin/projects/{project:id}/units/{unit?}', 'saveUnit')->whereNumber('unit')->middleware('throttle:property-upload')->name('admin.projects.unit');
    Route::post('/admin/projects/{project:id}/leads/{lead}', 'leadStatus')->whereNumber('lead')->name('admin.projects.lead');
    Route::post('/admin/projects/{project:id}/{child}/{id?}', 'saveChild')->whereIn('child', ['highlights', 'videos', 'faqs', 'charges'])->whereNumber('id')->name('admin.projects.child');
});
// Home loans: compare lenders, EMI / eligibility calculators and the application (income → offers → property → documents).
Route::controller(\App\Http\Controllers\HomeLoanController::class)->group(function () {
    Route::get('/home-loan', 'index')->name('home-loan.index');
    Route::post('/home-loan', 'start')->middleware('throttle:home-loan')->name('home-loan.start');
    Route::get('/home-loan/apply/{application}/{step}', 'step')->whereNumber('application')->whereIn('step', ['income', 'offers', 'property', 'documents'])->name('home-loan.step');
    Route::post('/home-loan/apply/{application}/{step}', 'save')->whereNumber('application')->whereIn('step', ['income', 'offers', 'property', 'documents'])->middleware('throttle:home-loan')->name('home-loan.save');
    Route::post('/home-loan/apply/{application}/upload', 'upload')->whereNumber('application')->middleware('throttle:home-loan')->name('home-loan.upload');
    Route::get('/home-loan/documents/{document}', 'document')->whereNumber('document')->name('home-loan.document');
    Route::post('/home-loan/documents/{document}/remove', 'removeDocument')->whereNumber('document')->name('home-loan.document.remove');
    Route::get('/my-home-loans', 'mine')->middleware('auth')->name('home-loan.mine');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\HomeLoanAdminController::class)->group(function () {
    Route::get('/admin/home-loans', 'index')->name('admin.home-loans.index');
    Route::post('/admin/home-loans/banks', 'saveBank')->name('admin.home-loans.bank.create');
    Route::post('/admin/home-loans/banks/{bank}', 'saveBank')->whereNumber('bank')->name('admin.home-loans.bank.update');
    Route::post('/admin/home-loans/{application}', 'update')->whereNumber('application')->name('admin.home-loans.update');
    Route::post('/admin/home-loans/{application}/delete', 'destroy')->whereNumber('application')->name('admin.home-loans.destroy');
});
// Property legal services: agreements, verification, deeds, registration, CA and khata services with a cart and bookings.
Route::controller(\App\Http\Controllers\LegalServiceController::class)->group(function () {
    Route::get('/legal-services/cart', 'cart')->name('legal.cart');
    Route::post('/legal-services/cart/{service}', 'add')->middleware('throttle:home-loan')->name('legal.cart.add');
    Route::post('/legal-services/cart/{service}/remove', 'remove')->name('legal.cart.remove');
    Route::post('/legal-services/checkout', 'checkout')->middleware(['auth', 'throttle:home-loan'])->name('legal.checkout');
    Route::get('/legal-services/bookings', 'bookings')->middleware('auth')->name('legal.bookings');
    Route::post('/legal-services/bookings/{booking}/review', 'review')->whereNumber('booking')->middleware(['auth', 'throttle:home-loan'])->name('legal.bookings.review');
    Route::post('/legal-services/bookings/{booking}/cancel', 'cancel')->whereNumber('booking')->middleware('auth')->name('legal.bookings.cancel');
    Route::get('/legal/{city?}', 'index')->where('city', '[a-z0-9-]+')->name('legal.index');
    Route::get('/legal/{city}/{service}', 'show')->where('city', '[a-z0-9-]+')->name('legal.show');
});
// Pay rent / tuition / maintenance by credit card (Paytm card charge → Cashfree payout; off until PAY_RENT_LIVE and both keys are set).
Route::get('/pay-rent', [\App\Http\Controllers\PayRentController::class, 'index'])->name('pay-rent.index');
Route::middleware('auth')->controller(\App\Http\Controllers\PayRentController::class)->group(function () {
    Route::get('/pay-rent/payments', 'mine')->name('pay-rent.mine');
    Route::get('/pay-rent/payments/{transfer:reference}', 'show')->name('pay-rent.show');
    Route::post('/pay-rent/payments/{transfer:reference}/pay', 'pay')->middleware('throttle:home-loan')->name('pay-rent.pay');
    Route::post('/pay-rent/payments/{transfer:reference}/delete', 'destroy')->name('pay-rent.destroy');
    Route::get('/pay-rent/{purpose}', 'purpose')->where('purpose', '[a-z]+')->name('pay-rent.purpose');
    Route::get('/pay-rent/{purpose}/{method}', 'form')->where(['purpose' => '[a-z]+', 'method' => 'contact|upi|bank'])->name('pay-rent.form');
    Route::post('/pay-rent/{purpose}', 'store')->where('purpose', '[a-z]+')->middleware('throttle:home-loan')->name('pay-rent.store');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\PayRentAdminController::class)->group(function () {
    Route::get('/admin/pay-rent', 'index')->name('admin.pay-rent.index');
    Route::post('/admin/pay-rent/{transfer}/retry', 'retry')->whereNumber('transfer')->name('admin.pay-rent.retry');
    Route::post('/admin/pay-rent/{transfer}/refunded', 'refunded')->whereNumber('transfer')->name('admin.pay-rent.refunded');
});
// Home interiors: design ideas, projects, room-wise estimator, style quiz and free quotes.
Route::controller(\App\Http\Controllers\InteriorController::class)->group(function () {
    Route::get('/interiors', 'index')->name('interiors.index');
    Route::get('/interiors/ideas', 'ideas')->name('interiors.ideas');
    Route::get('/interiors/ideas/{idea}', 'idea')->name('interiors.idea');
    Route::get('/interiors/projects/{project}', 'project')->name('interiors.project');
    Route::get('/interiors/estimator', 'estimator')->name('interiors.estimator');
    Route::get('/interiors/style-quiz', 'quiz')->name('interiors.quiz');
    Route::get('/interiors/quote', 'quote')->name('interiors.quote');
    Route::post('/interiors/quote', 'storeQuote')->middleware('throttle:home-loan')->name('interiors.quote.store');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\InteriorAdminController::class)->group(function () {
    Route::get('/admin/interiors', 'index')->name('admin.interiors.index');
    Route::post('/admin/interiors/leads/{lead}', 'updateLead')->whereNumber('lead')->name('admin.interiors.lead');
    Route::post('/admin/interiors/ideas', 'storeIdeas')->name('admin.interiors.ideas.store');
    Route::post('/admin/interiors/ideas/{idea:id}', 'updateIdea')->name('admin.interiors.ideas.update');
    Route::post('/admin/interiors/projects', 'storeProject')->name('admin.interiors.projects.store');
    Route::post('/admin/interiors/projects/{project:id}', 'storeProject')->name('admin.interiors.projects.update');
    Route::post('/admin/interiors/projects/{project:id}/delete', 'destroyProject')->name('admin.interiors.projects.destroy');
});
// Refer & Earn: owner contacts / board photos → verified by the owner → rewards → UPI withdrawals.
Route::get('/refer-and-earn', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
Route::middleware('auth')->controller(\App\Http\Controllers\ReferralController::class)->group(function () {
    Route::post('/refer-and-earn', 'store')->middleware('throttle:home-loan')->name('referrals.store');
    Route::get('/refer-and-earn/mine', 'mine')->name('referrals.mine');
    Route::post('/refer-and-earn/withdraw', 'withdraw')->middleware('throttle:home-loan')->name('referrals.withdraw');
    Route::get('/refer-and-earn/photos/{referral}', 'photo')->whereNumber('referral')->name('referrals.photo');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\ReferralAdminController::class)->group(function () {
    Route::get('/admin/referrals', 'index')->name('admin.referrals.index');
    Route::post('/admin/referrals/{referral}', 'review')->whereNumber('referral')->name('admin.referrals.review');
    Route::post('/admin/referrals/withdrawals/{withdrawal}', 'settle')->whereNumber('withdrawal')->name('admin.referrals.settle');
});
// Home services: cleaning, packers & movers, painting, repairs, appliances, pest control — booked by date / time slot.
Route::controller(\App\Http\Controllers\HomeServiceController::class)->group(function () {
    Route::get('/home-services', 'index')->name('home-services.index');
    Route::get('/home-services/bookings', 'bookings')->middleware('auth')->name('home-services.bookings');
    Route::post('/home-services/bookings/{booking}/review', 'review')->whereNumber('booking')->middleware(['auth', 'throttle:home-loan'])->name('home-services.review');
    Route::post('/home-services/bookings/{booking}/cancel', 'cancel')->whereNumber('booking')->middleware('auth')->name('home-services.cancel');
    Route::get('/home-services/{city}', 'city')->where('city', '[a-z0-9-]+')->name('home-services.city');
    Route::get('/home-services/{city}/{category}', 'category')->where(['city' => '[a-z0-9-]+', 'category' => '[a-z]+'])->name('home-services.category');
    Route::post('/home-services/{city}/{category}', 'store')->where(['city' => '[a-z0-9-]+', 'category' => '[a-z]+'])->middleware(['auth', 'throttle:home-loan'])->name('home-services.store');
});
Route::get('/home-services-in-{city}', fn (string $city) => redirect()->route('home-services.city', $city, 301))->where('city', '[a-z0-9-]+');
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\HomeServiceAdminController::class)->group(function () {
    Route::get('/admin/home-services', 'index')->name('admin.home-services.index');
    Route::post('/admin/home-services/bookings/{booking}', 'update')->whereNumber('booking')->name('admin.home-services.update');
    Route::post('/admin/home-services/reviews/{review}', 'toggleReview')->whereNumber('review')->name('admin.home-services.review');
    Route::post('/admin/home-services/services/{service}', 'saveService')->whereNumber('service')->name('admin.home-services.service');
});
// Property forum: questions and answers by category, upvotes, best answers, reports and top authors.
Route::controller(\App\Http\Controllers\ForumController::class)->group(function () {
    Route::get('/forum', 'index')->name('forum.index');
    Route::get('/forum/users/{user}', 'author')->whereNumber('user')->name('forum.author');
    Route::middleware('auth')->group(function () {
        Route::post('/forum/ask', 'ask')->middleware('throttle:home-loan')->name('forum.ask');
        Route::post('/forum/questions/{question}/answers', 'answer')->whereNumber('question')->middleware('throttle:home-loan')->name('forum.answer');
        Route::post('/forum/answers/{answer}/vote', 'vote')->whereNumber('answer')->middleware('throttle:property-contact')->name('forum.vote');
        Route::post('/forum/answers/{answer}/best', 'best')->whereNumber('answer')->name('forum.best');
        Route::post('/forum/answers/{answer}/delete', 'destroyAnswer')->whereNumber('answer')->name('forum.answer.destroy');
        Route::post('/forum/report/{type}/{id}', 'report')->whereIn('type', ['question', 'answer'])->whereNumber('id')->middleware('throttle:property-contact')->name('forum.report');
    });
    Route::get('/forum/{category}', 'category')->where('category', '[a-z-]+')->name('forum.category');
    Route::get('/forum/{category}/{question}', 'show')->where(['category' => '[a-z-]+', 'question' => '[0-9]+(-[a-z0-9-]+)?'])->name('forum.question');
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\ForumAdminController::class)->group(function () {
    Route::get('/admin/forum', 'index')->name('admin.forum.index');
    Route::post('/admin/forum/{type}/{id}', 'moderate')->whereIn('type', ['question', 'answer'])->whereNumber('id')->name('admin.forum.moderate');
});
// Online rent agreements: calculator, drafting steps, preview and order (becomes a legal services booking).
Route::controller(\App\Http\Controllers\RentAgreementController::class)->group(function () {
    Route::get('/rent-agreement/{city?}', 'index')->where('city', '[a-z0-9-]+')->name('rent-agreement.index');
    Route::middleware('auth')->group(function () {
        Route::post('/rent-agreements', 'start')->middleware('throttle:home-loan')->name('rent-agreement.start');
        Route::get('/rent-agreements/{agreement}/preview', 'preview')->whereNumber('agreement')->name('rent-agreement.preview');
        Route::get('/rent-agreements/{agreement}/draft', 'draft')->whereNumber('agreement')->name('rent-agreement.draft');
        Route::post('/rent-agreements/{agreement}/upload', 'upload')->whereNumber('agreement')->middleware('throttle:home-loan')->name('rent-agreement.upload');
        Route::post('/rent-agreements/{agreement}/options', 'options')->whereNumber('agreement')->name('rent-agreement.options');
        Route::post('/rent-agreements/{agreement}/delete', 'destroy')->whereNumber('agreement')->name('rent-agreement.destroy');
        Route::get('/rent-agreements/{agreement}/{step}', 'step')->whereNumber('agreement')->whereIn('step', ['parties', 'property', 'terms', 'review'])->name('rent-agreement.step');
        Route::post('/rent-agreements/{agreement}/{step}', 'save')->whereNumber('agreement')->whereIn('step', ['parties', 'property', 'terms', 'review'])->middleware('throttle:home-loan')->name('rent-agreement.save');
    });
});
Route::middleware(['auth', 'admin'])->controller(\App\Http\Controllers\LegalAdminController::class)->group(function () {
    Route::get('/admin/legal', 'index')->name('admin.legal.index');
    Route::post('/admin/legal/bookings/{booking}', 'update')->whereNumber('booking')->name('admin.legal.update');
    Route::post('/admin/legal/services/{service:id}', 'saveService')->name('admin.legal.service');
    Route::post('/admin/legal/partners', 'savePartner')->name('admin.legal.partner.create');
    Route::post('/admin/legal/partners/{partner}', 'savePartner')->whereNumber('partner')->name('admin.legal.partner.update');
    Route::post('/admin/legal/reviews/{review}', 'toggleReview')->whereNumber('review')->name('admin.legal.review');
});
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/{part}.xml', [\App\Http\Controllers\SitemapController::class, 'part'])->name('sitemap.part');
Route::get('/robots.txt', fn () => response(implode("
", ['User-agent: *', 'Disallow: /admin', 'Disallow: /employer', 'Disallow: /dashboard', 'Disallow: /profile', 'Disallow: /joining/', 'Disallow: /offer/', 'Disallow: /hiring/', 'Disallow: /trading/admin', 'Disallow: /trading/onboarding', 'Disallow: /api/', 'Disallow: /*report.pdf', 'Disallow: /*report.xlsx', '', 'Sitemap: ' . route('sitemap')]) . "
", 200, ['Content-Type' => 'text/plain']))->name('robots');
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
// Funding rounds and investors read from funding news; registered before /company-insights/{company:slug}.
Route::get('/company-insights/funding', [\App\Http\Controllers\FundingController::class, 'rounds'])->name('funding.rounds');
Route::get('/company-insights/investors', [\App\Http\Controllers\FundingController::class, 'investors'])->name('funding.investors');
Route::get('/company-insights/investors/{investor:slug}', [\App\Http\Controllers\FundingController::class, 'investor'])->name('funding.investor');
Route::get('/company-insights/people-flags', [\App\Http\Controllers\FundingController::class, 'people'])->name('funding.people');
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

Route::get('/health', fn () => response()->json(['status' => 'ok', 'deployed' => is_file($v = storage_path('app/deployed-version.txt')) ? trim(file_get_contents($v)) : null]))->name('health');
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
Route::post('/payments/paytm/callback', [\App\Http\Controllers\PaytmPaymentController::class, 'callback'])->name('payments.paytm.callback');
Route::middleware('auth')->group(function () {
    Route::post('/premium/checkout', [\App\Http\Controllers\PremiumController::class, 'checkout'])->middleware('throttle:10,1')->name('premium.checkout');
    Route::get('/premium/orders/{orderNo}', [\App\Http\Controllers\PremiumController::class, 'pay'])->name('premium.pay');
    Route::post('/premium/orders/{orderNo}/paytm', [\App\Http\Controllers\PaytmPaymentController::class, 'initiate'])->middleware('throttle:20,1')->name('payments.paytm.initiate');
    Route::get('/payments/{orderId}/status', [\App\Http\Controllers\PaytmPaymentController::class, 'status'])->where('orderId', '[A-Za-z0-9_]+')->name('payments.status');
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
Route::get("/career-explorer/places", [\App\Http\Controllers\CareerExplorerController::class,"places"])->middleware("throttle:60,1")->name("career-explorer.places");
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
