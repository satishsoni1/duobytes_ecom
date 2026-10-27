<?php

use App\Http\Controllers\Admin\ChannelPartnerController as AdminChannelPartnerController;
use App\Http\Controllers\ChannelPartner\AuthController;
use App\Http\Controllers\ChannelPartner\CartController;
use App\Http\Controllers\ChannelPartner\CatalogController;
use App\Http\Controllers\ChannelPartner\DashboardController;
use App\Http\Controllers\ChannelPartner\OnboardingController;
use App\Http\Controllers\ChannelPartner\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Channel Partner Routes
|--------------------------------------------------------------------------
*/

Route::prefix('partner')->name('channel_partner.')->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('/', 'landing')->name('landing');
        Route::middleware('guest')->group(function () {
            Route::get('/register', 'showRegister')->name('register');
            Route::post('/register', 'register')->name('register.store')->middleware('throttle:10,1');
            Route::get('/login', 'showLogin')->name('login');
            Route::post('/login', 'login')->name('login.attempt')->middleware('throttle:10,1');
        });
        Route::post('/logout', 'logout')->name('logout')->middleware('auth');
    });

    Route::middleware(['auth', 'channel_partner'])->group(function () {
        Route::controller(OnboardingController::class)->group(function () {
            Route::get('/onboarding', 'show')->name('onboarding');
            Route::post('/onboarding/business', 'saveBusiness')->name('onboarding.business');
            Route::post('/onboarding/documents', 'uploadDocument')->name('onboarding.documents.store')->middleware('throttle:30,1');
            Route::delete('/onboarding/documents/{document}', 'deleteDocument')->name('onboarding.documents.destroy');
            Route::post('/onboarding/submit', 'submit')->name('onboarding.submit');
            Route::get('/documents/{document}', 'viewDocument')->name('documents.show');
        });
    });

    Route::middleware(['auth', 'channel_partner:approved', 'prevent-back-history'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/account', [DashboardController::class, 'account'])->name('account');
        Route::post('/account/password', [DashboardController::class, 'updatePassword'])->name('account.password');

        Route::controller(CatalogController::class)->group(function () {
            Route::get('/catalog', 'index')->name('catalog');
            Route::get('/catalog/{slug}', 'show')->name('catalog.show');
            Route::post('/quote', 'quote')->name('quote');
        });

        Route::controller(CartController::class)->group(function () {
            Route::get('/cart', 'index')->name('cart');
            Route::post('/cart', 'store')->name('cart.store');
            Route::patch('/cart/{item}', 'update')->name('cart.update');
            Route::delete('/cart/{item}', 'destroy')->name('cart.destroy');
            Route::delete('/cart', 'clear')->name('cart.clear');
        });

        Route::controller(OrderController::class)->group(function () {
            Route::get('/checkout', 'checkout')->name('checkout');
            Route::post('/checkout', 'place')->name('checkout.place')->middleware('throttle:10,1');
            Route::get('/orders', 'index')->name('orders.index');
            Route::get('/orders/{code}', 'show')->name('orders.show');
            Route::get('/orders/{code}/invoice', 'invoice')->name('orders.invoice');
            Route::post('/orders/{code}/reorder', 'reorder')->name('orders.reorder');
        });
    });
});

Route::prefix('admin/channel-partners')
    ->name('admin.channel_partners.')
    ->middleware(['auth', 'admin', 'prevent-back-history'])
    ->controller(AdminChannelPartnerController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/invoice-sample', 'invoiceSample')->name('invoice_sample');
        Route::get('/{partner}', 'show')->name('show')->whereNumber('partner');
        Route::post('/{partner}/status', 'updateStatus')->name('status');
        Route::post('/{partner}/terms', 'updateTerms')->name('terms');
        Route::post('/{partner}/rules', 'storeRule')->name('rules.store');
        Route::put('/rules/{rule}', 'updateRule')->name('rules.update');
        Route::delete('/rules/{rule}', 'destroyRule')->name('rules.destroy');
        Route::get('/documents/{document}', 'document')->name('documents.show');
        Route::post('/documents/{document}/status', 'documentStatus')->name('documents.status');
    });
