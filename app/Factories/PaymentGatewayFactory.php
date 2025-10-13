<?php

namespace App\Factories;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\PaymentGateways\TriboPayGateway;
use App\Services\PaymentGateways\For4PaymentGateway;
use App\Services\PaymentGateways\StripeGateway;
use App\Services\PaymentGateways\AbacatePayGateway; // ← NOVO
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public static function create(string $gatewayName = null): PaymentGatewayInterface
    {
        // If no gateway name is provided, use the default from config
        $gatewayName = $gatewayName ?: config('services.default_payment_gateway');

        Log::channel('payment_checkout')->info('PaymentGatewayFactory: Creating gateway - ' . $gatewayName);

        switch (strtolower($gatewayName)) {
            case 'tribopay':
                return app(TriboPayGateway::class);
            case 'stripe':
                return app(StripeGateway::class);
            case 'for4payment':
                return app(For4PaymentGateway::class);
            case 'abacatepay': // ← NOVO
                return app(AbacatePayGateway::class);
            // Add other gateways here
            default:
                Log::channel('payment_checkout')->error('PaymentGatewayFactory: Invalid gateway specified - ' . $gatewayName);
                throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}");
        }
    }
}