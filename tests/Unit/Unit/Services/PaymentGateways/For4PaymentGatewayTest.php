<?php

namespace Tests\Unit\Services\PaymentGateways;

use Tests\TestCase;
use App\Services\PaymentGateways\For4PaymentGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;

class For4PaymentGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.for4payment.api_key', 'test_for4_key');
        Config::set('services.for4payment.api_url', 'https://api.for4payment.com');
        Log::shouldReceive('channel->info')->andReturnNull();
        Log::shouldReceive('channel->error')->andReturnNull();
    }

    #[Test]
    public function it_creates_card_token_successfully_placeholder()
    {
        $gateway = new For4PaymentGateway();
        $cardData = ['number' => '1111222233334444', 'cvv' => '456'];
        $response = $gateway->createCardToken($cardData);

        $this->assertEquals('success', $response['status']);
        $this->assertStringStartsWith('for4payment_fake_token_', $response['token']);
        $this->assertEquals($cardData, $response['token_data']);
    }

    #[Test]
    public function it_processes_payment_successfully_placeholder()
    {
        // 1. Mock Guzzle response
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'transaction_id' => 'f4p_txn_mocked123',
                'message' => 'Payment successful (mocked).',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $mockClient = new Client(['handler' => $handlerStack]);

        // 2. Create gateway and inject mock client
        $gateway = new For4PaymentGateway();

        $reflection = new \ReflectionClass($gateway);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($gateway, $mockClient);

        // 3. Call the method
        $paymentData = ['amount' => 2000, 'currency' => 'USD', 'token' => 'fake_token'];
        $response = $gateway->processPayment($paymentData);

        // 4. Assert based on the mocked API response and handleResponse logic
        $this->assertEquals('success', $response['status']);
        $this->assertEquals('f4p_txn_mocked123', $response['transaction_id']);
        $this->assertEquals('Payment successful (mocked).', $response['message']);
    }

    #[Test]
    public function handle_response_correctly_maps_success()
    {
        $gateway = new For4PaymentGateway();
        $apiResponseData = [
            'success' => true,
            'transaction_id' => 'f4p_txn_test123',
            'message' => 'Approved',
            'custom_field' => 'value'
        ];
        $response = $gateway->handleResponse($apiResponseData, 200);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('f4p_txn_test123', $response['transaction_id']);
        $this->assertEquals('Approved', $response['message']);
        $this->assertEquals($apiResponseData, $response['data']);
    }

    #[Test]
    public function handle_response_correctly_maps_failure()
    {
        $gateway = new For4PaymentGateway();
        $apiResponseData = [
            'success' => false,
            'message' => 'Declined',
            'errors' => ['code' => '101', 'detail' => 'Insufficient funds']
        ];
        $response = $gateway->handleResponse($apiResponseData, 400);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('Declined', $response['message']);
        $this->assertEquals(['code' => '101', 'detail' => 'Insufficient funds'], $response['errors']);
        $this->assertEquals($apiResponseData, $response['original_response']);
    }
}
