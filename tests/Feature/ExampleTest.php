<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Mock the external API calls that the PagePay component makes on mount
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
                    ]
                ]
            ], 200),
            config('services.stripe.api_url') . '/products/prod_monthly_123' => Http::response(['id' => 'prod_monthly_123', 'name' => 'Monthly Plan'], 200),
            config('services.stripe.api_url') . '/prices?product=prod_monthly_123&limit=100' => Http::response([
                'data' => [
                    ['id' => 'price_monthly_brl', 'unit_amount' => 3990, 'currency' => 'brl', 'recurring' => ['interval' => 'month'], 'active' => true]
                ]
            ], 200),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}