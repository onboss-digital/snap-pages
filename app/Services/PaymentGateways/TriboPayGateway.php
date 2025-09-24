<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class TriboPayGateway implements PaymentGatewayInterface
{
    protected $httpClient;
    protected $apiToken;
    protected $apiUrl;

    public function __construct()
    {
        $this->httpClient = new Client([
        'verify' => !env('APP_DEBUG'), // <- ignora verificação de certificado SSL
        'timeout' => 30,   // opcional: tempo limite
    ]);
        $this->apiToken = config('services.tribopay.api_token'); // Assuming you'll store the API token in config
        $this->apiUrl = config('services.tribopay.api_url'); // Assuming you'll store the API URL in config
    }

    public function createCardToken(array $cardData): array
    {
        return ['status' => 'success', 'token_data' => $cardData];
    }


    public function processPayment(array $paymentData): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // Sensitive data masking for logs
        $loggedData = $paymentData;
        if (isset($loggedData['card']['number'])) {
            $loggedData['card']['number'] = '**** **** **** ' . substr($paymentData['card']['number'], -4);
        }
        if (isset($loggedData['card']['cvv'])) {
            $loggedData['card']['cvv'] = '***';
        }

        try {
            Log::channel('payment_checkout')->info('TriboPayGateway: Preparing Checkout. Data:', $loggedData);

            $request = new Request(
                'POST',
                $this->apiUrl . 'transactions?api_token=' . $this->apiToken,
                $headers,
                json_encode($paymentData)
            );

            $res = $this->httpClient->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            $dataResponse = json_decode($responseBody, true);

            Log::channel('payment_checkout')->info('TriboPayGateway: API Response:', [
                'status_code' => $res->getStatusCode(),
                'body' => $dataResponse, // Log decoded response
                'timestamp' => now()
            ]);

            return $this->handleResponse($dataResponse, $res->getStatusCode());

        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('TriboPayGateway: API Error:', [
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

    public function handleResponse(array $responseData, int $statusCode = 200): array
    {
        // Based on TriboPay's typical responses, adapt this logic.
        // This is a generic example.
        if ($statusCode >= 200 && $statusCode < 300 && isset($responseData['success']) && $responseData['success']) {
            return [
                'status' => 'success',
                'transaction_id' => $responseData['transaction_id'] ?? null,
                'data' => $responseData
            ];
        } elseif (isset($responseData['errors'])) {
            return [
                'status' => 'error',
                'message' => 'Payment failed.',
                'errors' => $responseData['errors'],
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

    public function formatPlans(mixed $data, string $selectedCurrency): array
    {
        return []; //return response for function trbopay
    }
}
