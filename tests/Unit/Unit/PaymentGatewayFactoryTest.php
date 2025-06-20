<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Factories\PaymentGatewayFactory;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\PaymentGateways\TriboPayGateway;
use App\Services\PaymentGateways\For4PaymentGateway;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class PaymentGatewayFactoryTest extends TestCase
{
    #[Test]
    public function it_creates_tribopay_gateway_when_specified()
    {
        Config::set('services.default_payment_gateway', 'tribopay');
        $gateway = PaymentGatewayFactory::create('tribopay');
        $this->assertInstanceOf(TriboPayGateway::class, $gateway);
    }

    #[Test]
    public function it_creates_for4payment_gateway_when_specified()
    {
        Config::set('services.default_payment_gateway', 'for4payment');
        $gateway = PaymentGatewayFactory::create('for4payment');
        $this->assertInstanceOf(For4PaymentGateway::class, $gateway);
    }

    #[Test]
    public function it_creates_default_gateway_when_no_gateway_is_specified()
    {
        Config::set('services.default_payment_gateway', 'tribopay');
        $gateway = PaymentGatewayFactory::create();
        $this->assertInstanceOf(TriboPayGateway::class, $gateway);

        Config::set('services.default_payment_gateway', 'for4payment');
        $gateway = PaymentGatewayFactory::create();
        $this->assertInstanceOf(For4PaymentGateway::class, $gateway);
    }

    #[Test]
    public function it_throws_exception_for_unsupported_gateway()
    {
        $this->expectException(InvalidArgumentException::class);
        PaymentGatewayFactory::create('unsupported_gateway');
    }
}
