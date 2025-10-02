<?php

namespace Tests\Unit\Services\PaymentGateways;

use Tests\TestCase;
use App\Services\PaymentGateways\TriboPayGateway;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;

class TriboPayGatewayTest extends TestCase
{
    private $mockHttpClient;
    private $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.tribopay.api_token', 'test_tribo_token');
        Config::set('services.tribopay.api_url', 'https://api.tribopay.com.br');
        Log::shouldReceive('channel->info')->andReturnNull();
        Log::shouldReceive('channel->error')->andReturnNull();
    }

    private function getGatewayWithMockedClient(array $responses)
    {
        $this->mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockHttpClient = new Client(['handler' => $handlerStack]);

        $gateway = new TriboPayGateway();
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
            new GuzzleResponse(200, [], json_encode(['success' => true, 'transaction_id' => 'txn_123', 'data' => 'some_data']))
        ]);

        $paymentData = ['amount' => 1000, 'card' => ['number' => '1234', 'cvv' => '123']];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals('txn_123', $response['transaction_id']);
    }

    #[Test]
    public function it_handles_payment_failure_from_api()
    {
        $gateway = $this->getGatewayWithMockedClient([
            new GuzzleResponse(400, [], json_encode(['success' => false, 'errors' => ['Card declined']]))
        ]);

        $paymentData = ['amount' => 1000, 'card' => ['number' => '1234', 'cvv' => '123']];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
            // Check for the generic part of the message, as Guzzle's full message can be verbose
            $this->assertStringContainsString('Error processing payment: Client error:', $response['message']);
            // Optionally, check if the original error details are somewhat preserved if needed
            $this->assertStringContainsString('Card declined', $response['message']);
    }

    #[Test]
    public function it_handles_guzzle_exception_during_payment()
    {
        $this->mockHandler = new MockHandler([
             new \GuzzleHttp\Exception\RequestException("Error Communicating with Server", new \GuzzleHttp\Psr7\Request('POST', 'test'))
        ]);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockHttpClient = new Client(['handler' => $handlerStack]);

        $gateway = new TriboPayGateway();
        $reflection = new \ReflectionClass($gateway);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($gateway, $this->mockHttpClient);

        $paymentData = ['amount' => 1000, 'card' => ['number' => '1234', 'cvv' => '123']];
        $response = $gateway->processPayment($paymentData);

        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('Error processing payment', $response['message']);
    }

    #[Test]
    public function create_card_token_returns_data_as_is_for_tribopay()
    {
        $gateway = new TriboPayGateway();
        $cardData = ['number' => '1234567890123456', 'cvv' => '123'];
        $response = $gateway->createCardToken($cardData);

        $this->assertEquals('success', $response['status']);
        $this->assertEquals($cardData, $response['token_data']);
    }
}
