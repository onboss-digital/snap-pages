<?php

namespace App\Factories;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\PaymentGateways\TriboPayGateway;
use App\Services\PaymentGateways\For4PaymentGateway;
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
                return new TriboPayGateway();
            case 'for4payment':
                return new For4PaymentGateway();
            // Add other gateways here
            default:
                Log::channel('payment_checkout')->error('PaymentGatewayFactory: Invalid gateway specified - ' . $gatewayName);
                throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}");
        }
    }
}
