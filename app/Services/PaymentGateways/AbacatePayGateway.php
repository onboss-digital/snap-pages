<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class AbacatePayGateway implements PaymentGatewayInterface
{
    protected $httpClient;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->httpClient = new Client([
            'verify' => !env('APP_DEBUG'),
        ]);
        $this->apiKey = config('services.abacatepay.api_key');
        $this->apiUrl = config('services.abacatepay.api_url');
    }

    public function createCardToken(array $cardData): array
    {
        // Not applicable for PIX
        return ['status' => 'success', 'token' => null];
    }

    public function processPayment(array $paymentData): array
    {
        if ($paymentData['payment_method'] !== 'pix') {
            return ['status' => 'error', 'message' => 'AbacatePay gateway only supports PIX payments.'];
        }

        return $this->createPixCharge($paymentData);
    }

    public function createPixCharge(array $paymentData): array
    {
        $endpoint = $this->apiUrl . '/pixQrCode/create';

        $customer = $paymentData['customer'];
        $payload = [
            'amount' => $paymentData['amount'],
            'expiresIn' => $paymentData['expiresIn'] ?? 1800,
            'customer' => [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'cellphone' => $customer['phone_number'], // Correct key
                'taxId' => $customer['document'],     // Correct key
            ],
            'metadata' => $paymentData['metadata'] ?? [],
        ];

        try {
            Log::channel('pix_payment')->info('A enviar requisição para a API da Abacate Pay.', ['endpoint' => $endpoint, 'payload' => $payload]);
            $response = $this->httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            Log::channel('pix_payment')->info('Resposta recebida da API da Abacate Pay.', ['status_code' => $response->getStatusCode(), 'body' => $body]);

            if ($response->getStatusCode() === 200 && !isset($body['error'])) {
                return $this->handleResponse($body);
            } else {
                Log::channel('payment_checkout')->error('AbacatePay PIX creation failed', [
                    'response' => $body
                ]);
                return ['status' => 'error', 'message' => $body['error']['message'] ?? 'Failed to create PIX charge.'];
            }
        } catch (GuzzleException $e) {
            Log::channel('payment_checkout')->error('AbacatePay API communication error', [
                'error' => $e->getMessage(),
                'request_body' => $payload,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => 'error', 'message' => 'Could not connect to the payment gateway. Please try again later.'];
        }
    }

    public function checkPaymentStatus(string $pixId): array
    {
        $endpoint = $this->apiUrl . '/pixQrCode/check';

        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                 'query' => [
                    'id' => $pixId
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && !isset($body['error'])) {
                return ['status' => 'success', 'data' => ['status' => $body['data']['status']]];
            } else {
                return ['status' => 'error', 'message' => $body['error']['message'] ?? 'Failed to check PIX status.'];
            }
        } catch (GuzzleException $e) {
            return ['status' => 'error', 'message' => 'Could not connect to payment gateway.'];
        }
    }

    public function handleResponse(array $responseData): array
    {
        $data = $responseData['data'];
        return [
            'status' => 'success',
            'data' => [
                'pix_id' => $data['id'],
                'amount' => $data['amount'],
                'status' => $data['status'],
                'brCode' => $data['brCode'],
                'brCodeBase64' => $data['brCodeBase64'],
                'expires_at' => $data['expiresAt'],
            ]
        ];
    }

    public function formatPlans(mixed $data, string $selectedCurrency): array
    {
        // This gateway might not need to format plans if it's only for PIX
        return $data;
    }
}
