<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Features\Payment\Services\PaystackService;
use App\Features\Payment\Services\FlutterwaveService;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaystackService::class, function ($app) {
            $config = config('payment.gateways.paystack');
            return new PaystackService(
                $config['public_key'] ?? '',
                $config['secret_key'] ?? '',
                $config['payment_url'] ?? 'https://api.paystack.co'
            );
        });

        $this->app->singleton(FlutterwaveService::class, function ($app) {
            $config = config('payment.gateways.flutterwave');
            return new FlutterwaveService(
                $config['public_key'] ?? '',
                $config['secret_key'] ?? '',
                $config['encryption_key'] ?? '',
                $config['payment_url'] ?? 'https://api.flutterwave.com/v3',
                $config['webhook_secret_hash'] ?? ''
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
