<?php

namespace App\Providers;

use App\Services\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CartService as a singleton — same instance per request
        $this->app->singleton(CartService::class, fn () => new CartService());
    }

    public function boot(): void
    {
        // Make $gsCartCount available to every site/* view (the layout reads it)
        View::composer('site.*', function ($view) {
            $view->with('gsCartCount', app(CartService::class)->totalQuantity());
        });
    }
}
