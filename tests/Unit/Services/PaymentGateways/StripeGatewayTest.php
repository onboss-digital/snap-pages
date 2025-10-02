<?php

namespace Tests\Unit\Services\PaymentGateways;

use App\Services\PaymentGateways\StripeGateway;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.api_secret_key', 'sk_test_123');
        Config::set('services.stripe.api_url', 'https://api.stripe.com/v1');
        Log::shouldReceive('channel->info')->andReturnNull();
        Log::shouldReceive('channel->error')->andReturnNull();
    }

    private function getGatewayWithMockedClient(array $responses): StripeGateway
    {
        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockHttpClient = new Client(['handler' => $handlerStack]);

        return new StripeGateway($mockHttpClient);
    }

    #[Test]
    public function it_processes_payment_successfully()
    {
        $gateway = $this->getGatewayWithMockedClient([
            // 1. Check for existing customer
            new GuzzleResponse(200, [], json_encode(['data' => [['id' => 'cus_123']]])),
            // 2. Get payment method
            new GuzzleResponse(200, [], json_encode(['id' => 'pm_123', 'customer' => 'cus_123'])),
            // 3. Set default payment method for customer
            new GuzzleResponse(200, [], json_encode(['id' => 'cus_123'])),
            // 4. Setup Intent
            new GuzzleResponse(200, [], json_encode(['status' => 'succeeded'])),
            // 5. Get Price
            new GuzzleResponse(200, [], json_encode(['unit_amount' => 1000, 'currency' => 'usd', 'product' => 'prod_abc'])),
            // 6. Create Payment Intent
            new GuzzleResponse(200, [], json_encode(['status' => 'succeeded', 'id' => 'pi_123'])),
        ]);

        $paymentData = [
            'amount' => 1000,
            'payment_method_id' => 'pm_card_visa',
            'customer' => ['email' => 'test@test.com'],
            'cart' => [
                [
                    'product_hash' => 'prod_abc',
                    'price_id' => 'price_123',
                    'title' => 'Test Product',
                    'recurring' => false,
                ]
            ],
            'offer_hash' => 'offer_123',
            'upsell_url' => 'http://test.com/upsell'
        ];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('cus_123', $response['data']['customerId']);
    }

    #[Test]
    public function it_handles_payment_failure_from_api()
    {
        $gateway = $this->getGatewayWithMockedClient([
            new GuzzleResponse(400, [], json_encode([
                'error' => [
                    'message' => 'Card declined'
                ]
            ]))
        ]);

        $paymentData = ['amount' => 1000, 'payment_method_id' => 'pm_card_visa', 'customer' => ['email' => 'test@test.com'], 'cart' => []];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Card declined', $response['message']);
    }

    #[Test]
    public function it_handles_guzzle_exception_during_payment()
    {
        $mockHandler = new MockHandler([
             new \GuzzleHttp\Exception\RequestException(
                 "Error Communicating with Server",
                 new \GuzzleHttp\Psr7\Request('POST', 'test')
             )
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockHttpClient = new Client(['handler' => $handlerStack]);

        $gateway = new StripeGateway($mockHttpClient);

        $paymentData = ['amount' => 1000, 'payment_method_id' => 'pm_card_visa', 'customer' => ['email' => 'test@test.com'], 'cart' => []];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Error Communicating with Server', $response['message']);
    }

    #[Test]
    public function create_card_token_returns_error_as_expected()
    {
        $gateway = new StripeGateway();
        $cardData = ['number' => '4242424242424242', 'exp_month' => 12, 'exp_year' => 2034, 'cvc' => '567'];
        $response = $gateway->createCardToken($cardData);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Use Stripe.js para criar métodos de pagamento no front-end.', $response['message']);
    }
}