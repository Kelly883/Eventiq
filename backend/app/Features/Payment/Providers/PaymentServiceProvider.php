<?php

namespace App\Features\Payment\Providers;

use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaystackService::class, fn () => new PaystackService());
        $this->app->singleton(FlutterwaveService::class, fn () => new FlutterwaveService());
    }
}
