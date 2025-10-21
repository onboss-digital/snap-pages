<?php

namespace App\Services\PaymentGateways;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MercadoPagoGateway
{
    private string $accessToken;
    private string $baseUrl;
    private Client $client;

    public function __construct(Client $client = null)
    {
        // As credenciais serão obtidas do arquivo de configuração de serviços.
        // Vou adicionar essas configurações no próximo passo.
        $this->accessToken = config('services.mercadopago.access_token');
        $this->baseUrl = 'https://api.mercadopago.com'; // URL da API do Mercado Pago

        $this->client = $client ?: new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type'  => 'application/json',
            ],
            'verify' => !env('APP_DEBUG'),
        ]);
    }

    /**
     * Realiza uma requisição para a API do Mercado Pago.
     */
    private function request(string $method, string $endpoint, array $data = [])
    {
        try {
            $options = [];

            if (!empty($data)) {
                 $options['json'] = $data;
            }

            $response = $this->client->request(strtoupper($method), $this->baseUrl . $endpoint, $options);
            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            $body = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null;
            $errorMessage = $body['message'] ?? $e->getMessage();

            Log::channel('payment_checkout')->error('MercadoPagoGateway: API Error', [
                'message' => $errorMessage,
                'response' => $body
            ]);

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Gera um pagamento PIX.
     *
     * @param array $paymentData Dados do pagamento, incluindo 'transaction_amount', 'product_description', 'payer'.
     * @return array Resposta da API do Mercado Pago.
     * @throws \Exception Em caso de falha na requisição.
     */
    public function generatePix(array $paymentData): array
    {
        $payload = [
            'transaction_amount' => (float)$paymentData['transaction_amount'],
            'description'        => $paymentData['product_description'],
            'payment_method_id'  => 'pix',
            'payer' => [
                'email' => $paymentData['payer']['email'],
                'first_name' => $paymentData['payer']['first_name'],
                'last_name' => $paymentData['payer']['last_name'],
                'identification' => [
                    'type'   => 'CPF',
                    'number' => $paymentData['payer']['cpf'],
                ],
            ],
        ];

        $response = $this->request('post', '/v1/payments', $payload);

        if (!isset($response['id'])) {
            throw new \Exception('Failed to generate PIX payment.');
        }

        return [
            'mercado_pago_id' => $response['id'],
            'qr_code_base64'  => $response['point_of_interaction']['transaction_data']['qr_code_base64'],
            'qr_code'         => $response['point_of_interaction']['transaction_data']['qr_code'],
        ];
    }
}
