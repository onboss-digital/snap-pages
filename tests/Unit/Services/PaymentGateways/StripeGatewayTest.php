<?php

namespace Tests\Unit\Services\PaymentGateways;

use Tests\TestCase;
use App\Services\PaymentGateways\StripeGateway;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class StripeGatewayTest extends TestCase
{
    private $mockHttpClient;
    private $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.api_secret_key', 'sk_test_123');
        Config::set('services.stripe.api_url', 'https://api.stripe.com/v1');
        Log::shouldReceive('channel->info')->andReturnNull();
        Log::shouldReceive('channel->error')->andReturnNull();
    }

    private function getGatewayWithMockedClient(array $responses)
    {
        $this->mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockHttpClient = new Client(['handler' => $handlerStack]);

        $gateway = new StripeGateway();
        $reflection = new \ReflectionClass($gateway);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($gateway, $this->mockHttpClient);

        return $gateway;
    }

    #[Test]
    public function it_processes_payment_successfully()
    {
        $gateway = $this->getGatewayWithMockedClient([
            new GuzzleResponse(200, [], json_encode([
                'id' => 'pi_123',
                'status' => 'succeeded'
            ]))
        ]);

        $paymentData = ['amount' => 1000, 'payment_method' => 'pm_card_visa'];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('pi_123', $response['transaction_id']);
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

        $paymentData = ['amount' => 1000, 'payment_method' => 'pm_card_visa'];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('Error processing payment: Client error:', $response['message']);
        $this->assertStringContainsString('Card declined', $response['message']);
    }

    #[Test]
    public function it_handles_guzzle_exception_during_payment()
    {
        $this->mockHandler = new MockHandler([
             new \GuzzleHttp\Exception\RequestException(
                 "Error Communicating with Server",
                 new \GuzzleHttp\Psr7\Request('POST', 'test')
             )
        ]);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockHttpClient = new Client(['handler' => $handlerStack]);

        $gateway = new StripeGateway();
        $reflection = new \ReflectionClass($gateway);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($gateway, $this->mockHttpClient);

        $paymentData = ['amount' => 1000, 'payment_method' => 'pm_card_visa'];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('Error processing payment', $response['message']);
    }

    #[Test]
    public function create_card_token_returns_data_as_is_for_stripe()
    {
        $gateway = new StripeGateway();
        $cardData = ['number' => '4242424242424242', 'exp_month' => 12, 'exp_year' => 2034, 'cvc' => '567'];
        $response = $gateway->createCardToken($cardData);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals($cardData, $response['token_data']);
    }
}
