<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    protected Client $client;
    protected ?string $token;
    protected string $baseUri;

    public function __construct(Client $client = null)
    {
        $this->token = config('services.mercadopago.token');
        $this->baseUri = config('services.mercadopago.base_uri');

        if (!$this->token) {
            throw new \InvalidArgumentException('A chave de acesso (token) do Mercado Pago não está configurada no arquivo .env.');
        }

        $this->client = $client ?: new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Content-Type'  => 'application/json',
            ],
            'verify' => !env('APP_DEBUG'),
        ]);
    }

    /**
     * Executes a request to the Mercado Pago API.
     */
    private function request(string $method, string $endpoint, array $data = [])
    {
        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request(strtoupper($method), $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error('MercadoPago API Error', [
                'endpoint' => $endpoint,
                'error' => $responseBody
            ]);
            throw new \Exception("MercadoPago API error: " . $responseBody);
        }
    }

    /**
     * Creates a PIX payment request.
     */
    public function createPixPayment(array $paymentData): array
    {
        return $this->request('POST', '/v1/payments', $paymentData);
    }

    /**
     * Checks the status of a PIX payment.
     */
    public function checkPixStatus(string $paymentId): array
    {
        return $this->request('GET', "/v1/payments/{$paymentId}");
    }

    // Methods from PaymentGatewayInterface (placeholders)

    public function createCardToken(array $cardData): array
    {
        // Not applicable for PIX flow.
        return ['status' => 'error', 'message' => 'Not implemented.'];
    }

    public function processPayment(array $paymentData): array
    {
        // This method would be for credit card processing.
        return ['status' => 'error', 'message' => 'Not implemented. Use createPixPayment for PIX transactions.'];
    }

    public function handleResponse(array $responseData): array
    {
        // Not applicable for this PIX flow.
        return ['status' => 'error', 'message' => 'Not implemented.'];
    }

    public function formatPlans(mixed $data, string $selectedCurrency): array
    {
        // Not applicable for this PIX flow.
        return [];
    }
}
