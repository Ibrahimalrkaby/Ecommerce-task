<?php

namespace App\Providers;

use App\Services\PaymentGateways\CashGateway;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\PayPalGateway;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, function () {

            $service = new PaymentService();

            // ── Register all gateways here ─────────────────────────────────
            // To add a new gateway:
            //   1. Create: app/Services/PaymentGateways/MyNewGateway.php
            //   2. Implement: PaymentGatewayInterface
            //   3. Register: $service->registerGateway(new MyNewGateway());
            //   That's it! No other files need to change.
            // ──────────────────────────────────────────────────────────────

            $service->registerGateway(new CreditCardGateway());
            $service->registerGateway(new PayPalGateway());
            $service->registerGateway(new StripeGateway());
            $service->registerGateway(new CashGateway());

            return $service;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
