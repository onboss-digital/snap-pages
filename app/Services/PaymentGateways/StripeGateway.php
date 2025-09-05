<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGatewayInterface
{
    protected $httpClient;
    protected $apiToken;
    protected $apiUrl;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->apiToken = config('services.stripe.api_secret_key'); // Chave secreta Stripe
        $this->apiUrl = config('services.stripe.api_url'); // URL base do Stripe
    }

    /**
     * Cria um token de cartão no Stripe
     */
    public function createCardToken(array $cardData): array
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $request = new Request(
                'POST',
                $this->apiUrl . 'tokens',
                $headers,
                http_build_query([
                    'card[number]'    => $cardData['number'],
                    'card[exp_month]' => $cardData['exp_month'],
                    'card[exp_year]'  => $cardData['exp_year'],
                    'card[cvc]'       => $cardData['cvv'],
                ])
            );

            $res = $this->httpClient->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            $dataResponse = json_decode($responseBody, true);

            return [
                'status' => 'success',
                'token_data' => $dataResponse
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error creating card token: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Processa pagamento no Stripe via PaymentIntent
     */
    public function processPayment(array $paymentData): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        // Mask sensitive data in logs
        $loggedData = $paymentData;
        if (isset($loggedData['card']['number'])) {
            $loggedData['card']['number'] = '**** **** **** ' . substr($paymentData['card']['number'], -4);
        }
        if (isset($loggedData['card']['cvv'])) {
            $loggedData['card']['cvv'] = '***';
        }

        try {
            Log::channel('payment_checkout')->info('StripeGateway: Preparing Checkout. Data:', $loggedData);

            $request = new Request(
                'POST',
                $this->apiUrl . 'payment_intents',
                $headers,
                http_build_query([
                    'amount' => $paymentData['amount'], // em centavos
                    'currency' => $paymentData['currency'] ?? 'usd',
                    'payment_method' => $paymentData['payment_method'], // ex: tok_xxx ou pm_xxx
                    'confirmation_method' => 'automatic',
                    'confirm' => 'true',
                ])
            );

            $res = $this->httpClient->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            $dataResponse = json_decode($responseBody, true);

            Log::channel('payment_checkout')->info('StripeGateway: API Response:', [
                'status_code' => $res->getStatusCode(),
                'body' => $dataResponse,
                'timestamp' => now()
            ]);

            return $this->handleResponse($dataResponse, $res->getStatusCode());

        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('StripeGateway: API Error:', [
                'message' => $e->getMessage(),
                'request_data' => $loggedData,
            ]);
            return [
                'status' => 'error',
                'message' => 'Error processing payment: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Resposta
     */
    public function handleResponse(array $responseData, int $statusCode = 200): array
    {
        if ($statusCode >= 200 && $statusCode < 300 && isset($responseData['status']) && $responseData['status'] === 'succeeded') {
            return [
                'status' => 'success',
                'transaction_id' => $responseData['id'] ?? null,
                'data' => $responseData
            ];
        } elseif (isset($responseData['error'])) {
            return [
                'status' => 'error',
                'message' => 'Payment failed: ' . ($responseData['error']['message'] ?? 'Unknown'),
                'errors' => $responseData['error'],
                'original_response' => $responseData
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Unknown error or unsuccessful payment.',
                'original_response' => $responseData
            ];
        }
    }
}
