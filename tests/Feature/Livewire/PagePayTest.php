<?php

namespace Tests\Feature\Livewire;

use App\Factories\PaymentGatewayFactory;
use App\Livewire\PagePay;
use App\Services\PaymentGateways\For4PaymentGateway;
use App\Services\PaymentGateways\TriboPayGateway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagePayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.tribopay.api_token', 'test_tribo_token');
        Config::set('services.tribopay.api_url', 'https://api.tribopay.com.br');
        Config::set('services.for4payment.api_key', 'test_for4_key');
        Config::set('services.for4payment.api_url', 'https://api.for4payment.com');
        Log::shouldReceive('channel->info')->andReturnNull();
        Log::shouldReceive('channel->error')->andReturnNull();

        // Mock the external API calls
        Http::fake([
            config('services.streamit.api_url') . '/get-plans' => Http::response([
                'data' => [
                    [
                        'pages_product_external_id' => 'prod_monthly_123',
                        'duration' => 'month',
                        'duration_value' => 1,
                        'price' => 5000,
                        'name' => 'Monthly',
                        'pages_upsell_url' => 'http://test.com/upsell',
                        'order_bumps' => [],
                    ],
                    [
                        'pages_product_external_id' => 'cupxl',
                        'duration' => 'month',
                        'duration_value' => 6,
                        'price' => 30000,
                        'name' => 'Semi-Annual',
                        'pages_upsell_url' => null,
                        'order_bumps' => [],
                    ]
                ]
            ], 200),
            config('services.stripe.api_url') . '/products/prod_monthly_123' => Http::response(['id' => 'prod_monthly_123', 'name' => 'Monthly Plan'], 200),
            config('services.stripe.api_url') . '/prices?product=prod_monthly_123&limit=100' => Http::response([
                'data' => [
                    ['id' => 'price_monthly_brl', 'unit_amount' => 3990, 'currency' => 'brl', 'recurring' => ['interval' => 'month'], 'active' => true]
                ]
            ], 200),
            config('services.stripe.api_url') . '/products/cupxl' => Http::response(['id' => 'cupxl', 'name' => 'Semi-Annual Plan'], 200),
            config('services.stripe.api_url') . '/prices?product=cupxl&limit=100' => Http::response([
                'data' => [
                    ['id' => 'price_semiannual_brl', 'unit_amount' => 19990, 'currency' => 'brl', 'recurring' => ['interval' => 'month'], 'active' => true]
                ]
            ], 200),
        ]);
    }

    private function getFilledPagePayComponent()
    {
        return Livewire::test(PagePay::class)
            ->set('cardName', 'Test User')
            ->set('cardNumber', '4111111111111111')
            ->set('cardExpiry', '12/25')
            ->set('cardCvv', '123')
            ->set('email', 'test@example.com')
            ->set('phone', '+15551234567')
            ->set('selectedCurrency', 'BRL')
            ->set('cpf', '123.456.789-00') // Required for BRL
            ->set('selectedPlan', 'monthly');
    }

    #[Test]
    public function it_processes_checkout_successfully_with_tribopay()
    {
        Config::set('services.default_payment_gateway', 'tribopay');

        $this->instance(
            TriboPayGateway::class,
            Mockery::mock(TriboPayGateway::class, function (MockInterface $mock) {
                $mock->shouldReceive('processPayment')
                    ->once()
                    // The data passed to processPayment will be based on PagePay's prepareCheckoutData
                    ->with(Mockery::on(function ($data) {
                        return isset($data['amount']) && $data['amount'] > 0 && $data['card']['number'] === '4111111111111111';
                    }))
                    ->andReturn([
                        'status' => 'success',
                        'transaction_id' => 'tribo_txn_integration_123',
                        'redirect_url' => '/thank-you-tribopay'
                    ]);
            })
        );

        $this->getFilledPagePayComponent()
            ->call('startCheckout')
            ->assertHasNoErrors()
            ->assertRedirect('/thank-you-tribopay');
    }

    #[Test]
    public function it_processes_checkout_successfully_with_for4payment_placeholder()
    {
        Config::set('services.default_payment_gateway', 'for4payment');

        $this->instance(
            For4PaymentGateway::class,
            Mockery::mock(For4PaymentGateway::class, function (MockInterface $mock) {
                $mock->shouldReceive('processPayment')
                    ->once()
                    ->with(Mockery::on(function ($data) {
                        return isset($data['amount']) && $data['amount'] > 0 && $data['card']['number'] === '4111111111111111';
                    }))
                    ->andReturn([
                        'status' => 'success',
                        'transaction_id' => 'f4p_txn_integration_456',
                        'message' => 'Payment processed successfully (simulated by For4PaymentGateway).',
                        'redirect_url' => '/thank-you-for4payment'
                    ]);
            })
        );

        $this->getFilledPagePayComponent()
            ->call('startCheckout')
            ->assertHasNoErrors()
            ->assertRedirect('/thank-you-for4payment');
    }

    #[Test]
    public function it_handles_payment_failure_from_gateway_in_pagepay()
    {
        Config::set('services.default_payment_gateway', 'tribopay'); // or any gateway

        $this->instance(
            TriboPayGateway::class,
            Mockery::mock(TriboPayGateway::class, function (MockInterface $mock) { // Using TriboPay as an example
                $mock->shouldReceive('processPayment')
                    ->once()
                    ->andReturn([
                        'status' => 'error',
                        'message' => 'Gateway specific error message for PagePay',
                        'errors' => ['card_error' => 'Invalid card details from gateway']
                    ]);
            })
        );

        $this->getFilledPagePayComponent()
            ->call('startCheckout')
            ->assertHasErrors(['payment' => 'Gateway specific error message for PagePay Details: Invalid card details from gateway'])
            ->assertNotEmitted('redirect'); // Ensure no redirect event is emitted
    }

    #[Test]
    public function upsell_flow_then_checkout_with_gateway()
    {
        Config::set('services.default_payment_gateway', 'tribopay');

        $this->instance(
            TriboPayGateway::class,
            Mockery::mock(TriboPayGateway::class, function (MockInterface $mock) {
                $mock->shouldReceive('processPayment')
                    ->once()
                    // Check that the amount/plan reflects the accepted upsell (semi-annual)
                    ->with(Mockery::on(function($data){
                        // Find the semi-annual plan hash to verify
                        // This requires access to plan data, might be simpler to check amount
                        // or a known product identifier for semi-annual
                        // For now, let's assume PagePay correctly prepares data for "semi-annual"
                        return $data['offer_hash'] === 'cupxl'; // Hash for semi-annual from PagePay's getPlans
                    }))
                    ->andReturn([
                        'status' => 'success',
                        'transaction_id' => 'tribo_txn_upsell_789',
                        'redirect_url' => '/thank-you-upsell-tribopay'
                    ]);
            })
        );

        // Start with 'monthly', which triggers upsell modal
        $component = Livewire::test(PagePay::class)
            ->set('cardName', 'Test User Upsell')
            ->set('cardNumber', '4111111111111111')
            ->set('cardExpiry', '12/26')
            ->set('cardCvv', '124')
            ->set('email', 'testupsell@example.com')
            ->set('phone', '+15551234568')
            ->set('selectedCurrency', 'BRL')
            ->set('cpf', '987.654.321-00')
            ->set('selectedPlan', 'monthly'); // This plan triggers upsell

        // Call startCheckout, which should show upsell modal
        $component->call('startCheckout')
            ->assertSet('showUpsellModal', true)
            ->assertSet('showProcessingModal', false); // Should not be processing yet

        // Accept upsell
        $component->call('acceptUpsell')
            ->assertSet('selectedPlan', 'semi-annual') // Plan changed
            ->assertSet('showUpsellModal', false)
            ->assertSet('showProcessingModal', false) // Should be false before redirect
            ->assertHasNoErrors()
            ->assertRedirect('/thank-you-upsell-tribopay');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}