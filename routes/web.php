<?php

use App\Http\Controllers\ErpSiteProxyController;
use App\Http\Controllers\SitePageController;
use Illuminate\Support\Facades\Route;

Route::prefix('proxy/ymsoft-api')->group(function () {
    Route::get('/reservations/availability-layout', [ErpSiteProxyController::class, 'availabilityLayout'])
        ->name('erp.proxy.reservations.availability-layout');
    Route::get('/reservations/status-by-number', [ErpSiteProxyController::class, 'statusByNumber'])
        ->name('erp.proxy.reservations.status-by-number');
    Route::post('/reservations', [ErpSiteProxyController::class, 'storeReservation'])
        ->name('erp.proxy.reservations.store');
    Route::get('/self-order/menu', [ErpSiteProxyController::class, 'selfOrderMenu'])
        ->name('erp.proxy.self-order.menu');
    Route::post('/self-order/checkout', [ErpSiteProxyController::class, 'checkoutSelfOrder'])
        ->name('erp.proxy.self-order.checkout');
});

Route::get('/', [SitePageController::class, 'home'])->name('site.home');
Route::get('/brands', [SitePageController::class, 'brands'])->name('site.brands');
Route::get('/careers', [SitePageController::class, 'careers'])->name('site.careers');
Route::get('/careers/head-office', [SitePageController::class, 'careersScope'])
    ->defaults('scope', 'head-office')
    ->name('site.careers.head-office');
Route::get('/careers/outlet', [SitePageController::class, 'careersScope'])
    ->defaults('scope', 'outlet')
    ->name('site.careers.outlet');
Route::post('/careers/apply', [SitePageController::class, 'careersApply'])->name('site.careers.apply');
Route::get('/whats-on', [SitePageController::class, 'whatsOn'])->name('site.whats-on');
Route::get('/news/{id}', [SitePageController::class, 'newsDetail'])
    ->whereNumber('id')
    ->name('site.news.detail');
Route::get('/justus-apps', [SitePageController::class, 'justusApps'])->name('site.justus-apps');
Route::get('/home-service', [SitePageController::class, 'homeService'])->name('site.home-service');
Route::get('/about', [SitePageController::class, 'about'])->name('site.about');
Route::get('/reservation', [SitePageController::class, 'reservation'])->name('site.reservation');
Route::get('/reservation/arrange', [SitePageController::class, 'reservationArrange'])->name('site.reservation.arrange');
Route::get('/reservation/status', [SitePageController::class, 'reservationStatus'])->name('site.reservation.status');
