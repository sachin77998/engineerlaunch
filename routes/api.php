<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\TechnologyController;
use App\Http\Controllers\Api\JobIngestionController;
use App\Http\Middleware\CacheDiscoveryResponses;
use App\Http\Controllers\Api\OwnerAnalyticsController;
use App\Http\Controllers\Api\RoleAuthController;
use App\Http\Controllers\Api\SearchSuggestionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Job Portal API Routes
Route::middleware(CacheDiscoveryResponses::class)->prefix('jobs')->name('api.')->group(function () {
    Route::get('/', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/recent', [JobController::class, 'recentJobs'])->name('jobs.recent');
    Route::get('/trending', [JobController::class, 'trending'])->name('jobs.trending');
    Route::get('/stats', [JobController::class, 'stats'])->name('jobs.stats');
    Route::get('/categories', [JobController::class, 'categories'])->name('jobs.categories');
    Route::get('/company/{companyId}', [JobController::class, 'byCompany'])->name('jobs.by-company');
    Route::get('/{id}', [JobController::class, 'show'])->name('jobs.show');
});

Route::middleware(CacheDiscoveryResponses::class)->prefix('companies')->name('api.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/countries', [CompanyController::class, 'countries'])->name('companies.countries');
    Route::get('/sectors', [CompanyController::class, 'sectors'])->name('companies.sectors');
    Route::get('/top-hiring', [CompanyController::class, 'topHiring'])->name('companies.top-hiring');
    Route::get('/country/{country}', [CompanyController::class, 'byCountry'])->name('companies.by-country');
    Route::get('/{id}', [CompanyController::class, 'show'])->name('companies.show');
});

Route::middleware(CacheDiscoveryResponses::class)->prefix('technologies')->name('api.')->group(function () {
    Route::get('/', [TechnologyController::class, 'index'])->name('technologies.index');
    Route::get('/categories', [TechnologyController::class, 'categories'])->name('technologies.categories');
    Route::get('/trending', [TechnologyController::class, 'trending'])->name('technologies.trending');
    Route::get('/category/{category}', [TechnologyController::class, 'byCategory'])->name('technologies.by-category');
    Route::get('/{id}', [TechnologyController::class, 'show'])->name('technologies.show');
});

Route::get('/search/suggestions', SearchSuggestionController::class)
    ->middleware(['throttle:60,1', CacheDiscoveryResponses::class])
    ->name('api.search.suggestions');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/students/register', [RoleAuthController::class, 'studentRegister']);
    Route::post('/employers/register', [RoleAuthController::class, 'employerRegister']);
    Route::post('/{role}/login', [RoleAuthController::class, 'login'])->whereIn('role', ['student','employer','owner']);
});
Route::post('/auth/logout', [RoleAuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/auth/owners/register', [RoleAuthController::class, 'ownerRegister'])->middleware(['auth:sanctum','admin']);

Route::post('/ingestion/jobs', [JobIngestionController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('api.ingestion.jobs.store');

Route::get('/owner/analytics', OwnerAnalyticsController::class)
    ->middleware(['auth:sanctum', 'admin', 'throttle:60,1'])
    ->name('api.owner.analytics');
