<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Api;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\UpdatesController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->is_admin ? 'admin.dashboard' : 'dashboard')
    : redirect()->route('login'));

// Signing up and signing in
Route::middleware('guest')->group(function () {
    Route::get('/signup', [SignupController::class, 'show'])->name('signup');
    Route::post('/signup', [SignupController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Stripe and plugin updates (no session)
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
// The shop's website pulls its published settings and products from here (licence key in a header).
Route::prefix('api/v1')->middleware('throttle:120,1')->group(function () {
    Route::get('/site', [Api\SiteController::class, 'show'])->name('api.site');
    Route::post('/site/applied', [Api\SiteController::class, 'applied'])->name('api.site.applied');
    Route::get('/media', [Api\SiteController::class, 'media'])->middleware('signed')->name('api.media');
});

Route::get('/updates/check', [UpdatesController::class, 'check'])->middleware('throttle:60,1')->name('updates.check');
Route::get('/updates/download/{release}', [UpdatesController::class, 'download'])->middleware('signed')->name('updates.download');

// The plate maker's account
Route::middleware(['auth', 'customer'])->prefix('account')->group(function () {
    Route::get('/', [Account\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pay', [Account\BillingController::class, 'pay'])->name('pay');
    Route::get('/paid', [Account\BillingController::class, 'paid'])->name('paid');
    Route::get('/billing', [Account\BillingController::class, 'index'])->name('billing');
    Route::post('/billing/portal', [Account\BillingController::class, 'portal'])->name('billing.portal');

    Route::middleware('paid')->group(function () {
        Route::get('/details', [Account\DetailsController::class, 'edit'])->name('details');
        Route::post('/details', [Account\DetailsController::class, 'update']);
        Route::post('/details/logo', [Account\DetailsController::class, 'logo'])->name('details.logo');
        Route::post('/details/fonts', [Account\DetailsController::class, 'fonts'])->name('details.fonts');
        Route::post('/details/documents', [Account\DetailsController::class, 'document'])->name('details.document');
        Route::get('/files/{kind}', [Account\DetailsController::class, 'file'])->name('details.file');

        Route::get('/builder', [Account\BuilderController::class, 'edit'])->name('builder');
        Route::post('/builder', [Account\BuilderController::class, 'update']);
        Route::post('/publish', Account\PublishController::class)->middleware('throttle:20,1')->name('publish');
        Route::get('/emails', [Account\EmailsController::class, 'edit'])->name('emails');
        Route::post('/emails', [Account\EmailsController::class, 'update'])->middleware('throttle:20,1');
        Route::post('/extra-work', [Account\ProductsController::class, 'addons'])->name('products.addons');

        Route::get('/products', [Account\CatalogueController::class, 'index'])->name('products');
        Route::get('/products/new', [Account\CatalogueController::class, 'create'])->name('products.create');
        Route::post('/products/new', [Account\CatalogueController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [Account\CatalogueController::class, 'edit'])->name('products.edit');
        Route::post('/products/{product}', [Account\CatalogueController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/delete', [Account\CatalogueController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/move', [Account\CatalogueController::class, 'move'])->name('products.move');
        Route::get('/products/{product}/images/{n}', [Account\CatalogueController::class, 'image'])->whereNumber('n')->name('products.image');
    });

    Route::get('/support', [Account\SupportController::class, 'index'])->name('support');
    Route::post('/support', [Account\SupportController::class, 'store']);
    Route::get('/support/{ticket}', [Account\SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}', [Account\SupportController::class, 'reply'])->name('support.reply');
});

// Your back end
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/shops', [Admin\ShopController::class, 'index'])->name('shops');
    Route::get('/shops/{shop}', [Admin\ShopController::class, 'show'])->name('shops.show');
    Route::post('/shops/{shop}', [Admin\ShopController::class, 'update'])->name('shops.update');
    Route::post('/shops/{shop}/licence', [Admin\ShopController::class, 'licence'])->name('shops.licence');
    Route::get('/shops/{shop}/files/{kind}', [Admin\ShopController::class, 'file'])->name('shops.file');
    Route::get('/shops/{shop}/export', [Admin\ShopController::class, 'export'])->name('shops.export');

    Route::get('/documents', [Admin\DocumentController::class, 'index'])->name('documents');
    Route::get('/documents/{document}', [Admin\DocumentController::class, 'file'])->name('documents.file');
    Route::post('/documents/{document}', [Admin\DocumentController::class, 'review'])->name('documents.review');

    Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets');
    Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}', [Admin\TicketController::class, 'reply'])->name('tickets.reply');

    Route::get('/payments', [Admin\PaymentController::class, 'index'])->name('payments');
    Route::post('/addons/{addon}', [Admin\PaymentController::class, 'invoiceAddon'])->name('addons.invoice');

    Route::get('/releases', [Admin\ReleaseController::class, 'index'])->name('releases');
    Route::post('/releases', [Admin\ReleaseController::class, 'store']);
});
