<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbacatePayGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $apiUrl;
    private string $returnUrl;
    private string $completionUrl;

    public function __construct()
    {
        $this->apiKey = config('services.abacatepay.api_key');
        $this->apiUrl = config('services.abacatepay.api_url', 'https://api.abacatepay.com/v1');
        $this->returnUrl = config('services.abacatepay.return_url', 'https://web.snaphubb.online/obg/');
        $this->completionUrl = config('services.abacatepay.completion_url', 'https://web.snaphubb.online/obg/');

        if (!$this->apiKey) {
            throw new \Exception('AbacatePay API Key not configured');
        }
    }

    /**
     * Process payment via AbacatePay PIX
     *
     * @param array $data
     * @return array
     */
    public function processPayment(array $data): array
    {
        try {
            Log::channel('payment_checkout')->info('AbacatePay: Processing PIX payment', [
                'customer' => $data['customer']['email'] ?? 'unknown',
                'amount' => $data['amount'],
            ]);

            // Preparar dados para AbacatePay
            $pixData = $this->preparePixData($data);

            // Fazer requisição para API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/billing/create', $pixData);

            // Log da resposta
            Log::channel('payment_checkout')->info('AbacatePay: API Response', [
                'status_code' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                return $this->formatSuccessResponse($result, $data);
            }

            // Erro na API
            return $this->formatErrorResponse($response);

        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('AbacatePay: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Erro ao processar pagamento PIX. Por favor, tente novamente.',
            ];
        }
    }

    /**
     * Prepare PIX data for AbacatePay API
     *
     * @param array $data
     * @return array
     */
    private function preparePixData(array $data): array
    {
        // Preparar produtos
        $products = [];
        foreach ($data['cart'] as $item) {
            $products[] = [
                'externalId' => $item['product_hash'] ?? uniqid('prod_'),
                'name' => $item['title'] ?? 'Produto',
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'], // em centavos
            ];
        }

        // Limpar CPF (remover pontos e traços)
        $cpf = isset($data['customer']['cpf']) 
            ? preg_replace('/\D/', '', $data['customer']['cpf']) 
            : '';

        // Limpar telefone
        $phone = isset($data['customer']['phone']) 
            ? preg_replace('/\D/', '', $data['customer']['phone']) 
            : '';

        return [
            'frequency' => 'ONE_TIME',
            'methods' => ['PIX'],
            'products' => $products,
            'customer' => [
                'name' => $data['customer']['name'] ?? 'Cliente',
                'email' => $data['customer']['email'] ?? '',
                'cellphone' => $phone,
                'taxId' => $cpf,
            ],
            'returnUrl' => $this->returnUrl,
            'completionUrl' => $this->completionUrl,
            'metadata' => [
                'source' => 'snaphubb',
                'currency' => $data['currency_code'] ?? 'BRL',
                'utm_source' => $data['utm_source'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
            ],
        ];
    }

    /**
     * Format success response
     *
     * @param array $result
     * @param array $originalData
     * @return array
     */
    private function formatSuccessResponse(array $result, array $originalData): array
    {
        // Extrair dados do QR Code
        $qrCode = $result['pix']['qrcode'] ?? [];
        $brCode = $qrCode['code'] ?? '';
        $base64 = $qrCode['base64'] ?? '';

        // Calcular data de expiração (30 minutos por padrão)
        $expiresAt = isset($result['expiresAt']) 
            ? $result['expiresAt'] 
            : now()->addMinutes(30)->toIso8601String();

        return [
            'status' => 'success',
            'data' => [
                'pix_id' => $result['id'],
                'brCode' => $brCode,
                'brCodeBase64' => $base64 ? 'data:image/png;base64,' . $base64 : null,
                'amount' => $originalData['amount'],
                'status' => $result['status'] ?? 'PENDING',
                'expires_at' => $expiresAt,
                'url' => $result['url'] ?? null,
                'customer' => [
                    'name' => $originalData['customer']['name'] ?? '',
                    'email' => $originalData['customer']['email'] ?? '',
                ],
            ],
        ];
    }

    /**
     * Format error response
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return array
     */
    private function formatErrorResponse($response): array
    {
        $statusCode = $response->status();
        $body = $response->json();

        $errorMessage = 'Erro ao gerar PIX.';

        if (isset($body['message'])) {
            $errorMessage = $body['message'];
        } elseif (isset($body['error'])) {
            $errorMessage = $body['error'];
        }

        Log::channel('payment_checkout')->error('AbacatePay: API Error', [
            'status_code' => $statusCode,
            'body' => $body,
        ]);

        return [
            'status' => 'error',
            'message' => $errorMessage,
            'errors' => $body['errors'] ?? [],
        ];
    }

    /**
     * Get payment gateway name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'AbacatePay';
    }

    /**
     * Check if gateway supports refunds
     *
     * @return bool
     */
    public function supportsRefunds(): bool
    {
        return false; // PIX não suporta estorno automático
    }

    /**
     * Process refund (not supported for PIX)
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function processRefund(string $transactionId, float $amount): array
    {
        return [
            'status' => 'error',
            'message' => 'Estornos PIX devem ser processados manualmente.',
        ];
    }

    /**
     * Check payment status
     *
     * @param string $pixId
     * @return array
     */
    public function checkPaymentStatus(string $pixId): array
    {
        try {
            Log::channel('payment_checkout')->info('AbacatePay: Checking payment status', [
                'pix_id' => $pixId,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->apiUrl . '/billing/' . $pixId);

            Log::channel('payment_checkout')->info('AbacatePay: Status check response', [
                'status_code' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                return [
                    'status' => 'success',
                    'data' => [
                        'status' => $result['status'] ?? 'UNKNOWN',
                        'pix_id' => $pixId,
                    ],
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Erro ao verificar status do pagamento.',
            ];

        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('AbacatePay: Exception checking status', [
                'pix_id' => $pixId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Erro ao verificar status do pagamento.',
            ];
        }
    }
}
