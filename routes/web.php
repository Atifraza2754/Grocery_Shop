<?php

use App\Http\Controllers\Site\CartController;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\OrderController;
use App\Http\Controllers\Site\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer-facing storefront routes
|--------------------------------------------------------------------------
*/

Route::get('/',                  [HomeController::class, 'index'])->name('site.home');

Route::get('/products/{slug}',   [ProductController::class, 'show'])->name('site.product.show');

Route::get('/cart',          [CartController::class, 'index'])->name('site.cart');
Route::post('/cart/add',     [CartController::class, 'add'])->name('site.cart.add');
Route::post('/cart/update',  [CartController::class, 'update'])->name('site.cart.update');
Route::post('/cart/remove',  [CartController::class, 'remove'])->name('site.cart.remove');
Route::post('/cart/coupon',  [CartController::class, 'coupon'])->name('site.cart.coupon');
Route::get('/cart/count',    [CartController::class, 'count'])->name('site.cart.count');

Route::get('/checkout',          [CheckoutController::class, 'show'])->name('site.checkout');
Route::post('/checkout',         [CheckoutController::class, 'place'])->name('site.checkout.place');

Route::get('/order/{orderNo}',   [OrderController::class, 'show'])->name('site.order.show');
Route::get('/track',             [OrderController::class, 'trackForm'])->name('site.track');
Route::post('/track',            [OrderController::class, 'trackLookup'])->name('site.track.lookup');

/*
|--------------------------------------------------------------------------
| Backwards-compatible aliases
|--------------------------------------------------------------------------
*/
Route::get('/home', fn () => redirect()->route('site.home'));
Route::redirect('/welcome', '/');

// Note: /admin routes are owned by Filament — do not redefine here.
