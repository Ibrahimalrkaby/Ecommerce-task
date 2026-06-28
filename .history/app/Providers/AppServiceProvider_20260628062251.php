<?php

namespace App\Providers;

use App\Services\PaymentService;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentGateways\PaypalGateway;
use App\Services\PaymentGateways\CreditCardGateway;
use App\Services\PaymentGateways\CashGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentService::class, function () {
            $service = new PaymentService();
            $service->registerGateway(new StripeGateway());
            $service->registerGateway(new PaypalGateway());
            $service->registerGateway(new CreditCardGateway());
            $service->registerGateway(new CashGateway());
            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
}
