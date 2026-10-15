<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorComparisonController;
Route::prefix('admin/insights/vendors')->middleware(['auth','admin'])->name('insights.vendors.')->group(function(){
 Route::get('/',[VendorComparisonController::class,'index'])->name('index');
 Route::get('/template',[VendorComparisonController::class,'template'])->name('template');
 Route::post('/history',[VendorComparisonController::class,'store'])->middleware('throttle:10,1')->name('store');
});
