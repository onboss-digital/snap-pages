<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class StripeGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $redirectURL = "https://web.snaphubb.online/obg/";
    private Client $client;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.api_secret_key');
        $this->baseUrl = config('services.stripe.api_url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'verify' => !env('APP_DEBUG'),
        ]);
    }

    private function request(string $method, string $endpoint, array $params = [])
    {
        try {
            $options = [];

            if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
                $options['form_params'] = $params; // Stripe espera x-www-form-urlencoded
            } elseif (!empty($params)) {
                $options['query'] = $params; // GET com query string
            }

            $response = $this->client->request(strtoupper($method), $this->baseUrl . $endpoint, $options);
            dump($response);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $body = $e->getResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null;
            $errorMessage = $body['error']['message'] ?? $e->getMessage();
            dump($errorMessage);
            throw new \Exception($errorMessage);
        }
    }

    public function createCardToken(array $cardData): array
    {
        return [
            'status' => 'error',
            'message' => 'Use Stripe.js para criar métodos de pagamento no front-end.',
        ];
    }

    public function processPayment(array $paymentData): array
    {
        try {
            $paymentMethodId = $paymentData['payment_method_id'] ?? null;
            if (!$paymentMethodId) {
                return ['status' => 'error', 'message' => 'Missing payment method ID'];
            }

            $email = $paymentData['customer']['email'] ?? null;

            // 🔍 Buscar cliente existente
            $existing = $this->request('get', '/customers', [
                'email' => $email,
                'limit' => 1,
            ]);

            if (!empty($existing['data'])) {
                $customer = $existing['data'][0];
            } else {
                $customer = $this->request('post', '/customers', [
                    'name'  => $paymentData['customer']['name'] ?? 'Cliente',
                    'email' => $email,
                    'phone' => $paymentData['customer']['phone_number'] ?? null,
                ]);
            }

            // 🔗 Buscar PaymentMethod
            $paymentMethod = $this->request('get', "/payment_methods/{$paymentMethodId}");

            if (empty($paymentMethod['customer'])) {
                $this->request('post', "/payment_methods/{$paymentMethodId}/attach", [
                    'customer' => $customer['id'],
                ]);
            } elseif ($paymentMethod['customer'] !== $customer['id']) {
                // Reusar o mesmo customer do cartão
                $customer['id'] = $paymentMethod['customer'];
            }

            // Definir PaymentMethod como padrão
            $this->request('post', "/customers/{$customer['id']}", [
                'invoice_settings[default_payment_method]' => $paymentMethodId,
            ]);

            $results = [];

            // 0) Validar o payment_method antes de tudo
            $setupIntent = $this->request('post', '/setup_intents', [
                'customer' => $customer['id'],
                'payment_method' => $paymentMethodId,
                'confirm' => 'true',
                'usage' => 'off_session', // 🔹 garante que pode cobrar recorrente depois
                'automatic_payment_methods' => [
                    'enabled' => 'true',
                    'allow_redirects' => 'never' // 🔹 bloqueia redirect
                ]
            ]);

            if ($setupIntent['status'] !== 'succeeded') {
                return [
                    'status' => 'error',
                    'errors' => ["Falha ao validar método de pagamento ({$setupIntent['status']})"]
                ];
            }

            // 1) Criar cobranças (agora temos certeza que o cartão é válido)
            foreach ($paymentData['cart'] as $product) {
                $isRecurring = !empty($product['recurring']);

                if ($isRecurring) {
                    // Criar assinatura já ativa (não precisa pré-autorizar)
                    $subscriptionData = [
                        'customer' => $customer['id'],
                        'items' => [
                            ['price' => $product['price_id'], 'quantity' => $product['quantity'] ?? 1],
                        ],
                        'default_payment_method' => $paymentMethodId,
                        'payment_behavior' => 'allow_incomplete', // ⚡ permite criar e cobrar
                        'collection_method' => 'charge_automatically',
                        'expand' => ['latest_invoice.payment_intent'],
                        'metadata' => [
                            'price_id' => $product['price_id'],
                            'product_id' => $product['product_hash'],
                            'title' => $product['title']
                        ]
                    ];

                    $sub = $this->request('post', '/subscriptions', $subscriptionData);

                    // ⚡ Confirmar pagamento da primeira fatura
                    if (!empty($sub['latest_invoice']['payment_intent'])) {
                        $pi = $sub['latest_invoice']['payment_intent'];
                        if ($pi['status'] === 'requires_payment_method') {
                            $piConfirmed = $this->request('post', "/payment_intents/{$pi['id']}/confirm", [
                                'payment_method' => $paymentMethodId
                            ]);
                            $results[] = $piConfirmed;
                        }
                    }
                    $results[] = $sub;
                } else {
                    // Produto avulso -> pode criar PaymentIntent normalmente
                    $price = $this->request('get', "/prices/{$product['price_id']}");

                    $intent = $this->request('post', '/payment_intents', [
                        'customer' => $customer['id'],
                        'payment_method' => $paymentMethodId,
                        'amount' => $price['unit_amount'],
                        'currency' => $price['currency'],
                        'confirm' => 'true',
                        'capture_method' => 'automatic', // 🔹 agora pode cobrar direto
                        'description' => "Pagamento {$product['title']}",
                        'automatic_payment_methods' => [
                            'enabled' => 'true',
                            'allow_redirects' => 'never'
                        ],
                        'metadata' => [
                            'price_id' => $product['price_id'],
                            'product_id' => $price['product'],
                            'title' => $product['title']
                        ]
                    ]);

                    $results[] = $intent;
                }
            }

            return [
                'status' => 'success',
                'data' => [
                    'customerId' => $customer['id'],
                    'upsell_productId' => $paymentData['offer_hash'],
                    'redirect_url' => $paymentData['upsell_url']
                ]
            ];
        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('StripeGateway: API Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    public function handleResponse(array $responseData): array
    {
        if (($responseData['status'] ?? null) === 'succeeded') {
            return [
                'status' => 'success',
                'transaction_id' => $responseData['id'] ?? null,
                'redirect_url' => $this->redirectURL . "?id=" . ($responseData['payment_method'] ?? ''),
                'data' => $responseData,
            ];
        }

        return [
            'status' => 'error',
            'message' => $responseData['last_payment_error']['message'] ?? 'Payment failed.',
            'data' => $responseData,
        ];
    }

    public function getProductWithPrices(string $productId): array
    {
        try {
            // 🔹 Buscar produto e preços associados em paralelo (se sua request suportar)
            $product   = $this->request('get', "/products/{$productId}");
            $pricesRes = $this->request('get', '/prices', [
                'product' => $productId,
                'limit'   => 100,
            ]);

            // 🔹 Filtra apenas os ativos
            $activePrices = array_filter($pricesRes['data'] ?? [], static function ($price) {
                return $price['active'] === true;
            });

            // 🔹 Mapeia os dados que você precisa
            $prices = array_map(static function ($price) {
                return [
                    'id'          => $price['id'] ?? null,
                    'unit_amount' => $price['unit_amount'] ?? null,
                    'currency'    => strtoupper($price['currency'] ?? ''),
                    'recurring'   => $price['recurring'] ?? null,
                ];
            }, $activePrices);

            return [
                'status'  => 'success',
                'product' => [
                    'id'          => $product['id'] ?? null,
                    'name'        => $product['name'] ?? null,
                    'description' => $product['description'] ?? null,
                ],
                'prices'  => array_filter($prices, fn($p) => !empty($p['id'])), // remove inválidos
            ];
        } catch (\Throwable $e) {
            Log::channel('payment_checkout')->error('StripeGateway: getProductWithPrices Error', [
                'message'    => $e->getMessage(),
                'product_id' => $productId,
            ]);

            return [
                'status'  => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function formatPlans(mixed $data, string $selectedCurrency): array
    {
        $normalizeAmount = static function ($amount): ?float {
            if ($amount === null) return null;
            if (is_numeric($amount)) return round((float)$amount / (ctype_digit((string)$amount) ? 100 : 1), 2);
            return null;
        };

        $result = [];

        foreach ($data['data'] ?? [] as $plan) {
            if (empty($plan['pages_product_external_id'])) {
                continue;
            }

            // 🔹 Definição da chave do plano
            $key = match ($plan['duration']) {
                'week'  => 'weekly',
                'month' => match ((int)$plan['duration_value']) {
                    3       => 'quarterly',
                    6       => 'semi-annual',
                    default => 'monthly',
                },
                'year'  => 'annual',
                default => $plan['duration'],
            };

            // 🔹 Order Bumps
            $bumps = array_values(array_filter(array_map(function ($order_bump) use ($selectedCurrency, $normalizeAmount) {
                if (empty($order_bump['external_id'])) return null;

                $response = $this->getProductWithPrices($order_bump['external_id']);
                if (($response['status'] ?? 'error') !== 'success') return null;

                $prices = [];
                foreach ($response['prices'] as $p) {
                    $currency = strtoupper($p['currency'] ?? '');
                    if ($currency === '') continue;

                    $amount = $normalizeAmount($p['unit_amount'] ?? $p['amount'] ?? null);
                    if ($amount === null) continue;

                    $prices[$currency] = [
                        'id'       => $p['id'],
                        'price'    => $amount,
                        'recurring' => $p['recurring'] ?? null,
                    ];
                }

                return isset($prices[$selectedCurrency]) ? [
                    'id'          => $order_bump['external_id'],
                    'title'       => $order_bump['title'],
                    'text_button' => $order_bump['text_button'],
                    'description' => $order_bump['description'],
                    'price'       => $prices[$selectedCurrency]['price'],
                    'price_id'    => $prices[$selectedCurrency]['id'],
                    'recurring' => $prices[$selectedCurrency]['recurring'] ?? null,
                    'currency'    => $selectedCurrency,
                    'hash'        => $order_bump['external_id'],
                    'active'      => false,
                ] : null;
            }, $plan['order_bumps'] ?? [])));

            // 🔹 Buscar preços do produto principal
            $gatewayResponse = $this->getProductWithPrices($plan['pages_product_external_id']);
            $prices = [];

            foreach (($gatewayResponse['prices'] ?? []) as $p) {
                $currency = strtoupper($p['currency'] ?? '');
                if ($currency === '') continue;

                $amount = $normalizeAmount($p['unit_amount'] ?? $p['amount'] ?? null);
                if ($amount === null) continue;

                $prices[$currency] = [
                    'id'            => $p['id'],
                    'origin_price'  => $plan['price'],
                    'descont_price' => $amount,
                    'recurring'     => $p['recurring'] ?? null,
                ];
            }

            $result[$key] = [
                'hash'          => $plan['pages_product_external_id'],
                'label'         => ucfirst($plan['name']) . " - {$plan['duration_value']}/{$plan['duration']}",
                'nunber_months' => $plan['duration_value'],
                'prices'        => $prices,
                'upsell_url' => $plan['pages_upsell_url'],
                'order_bumps'   => $bumps,
            ];
        }
        return $result;
    }
}
