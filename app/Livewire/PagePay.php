<?php

namespace App\Livewire;

use App\Factories\PaymentGatewayFactory;
use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PagePay extends Component
{
    public $paymentMethodId, $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone, $cpf,
        $plans = [], $modalData = [], $product = [];

    // Modais
    public $showSecure = false;
    public $showLodingModal = false;
    public $showDownsellModal = false;
    public $showUpsellModal = false;
    public $showProcessingModal = false;

    public $selectedCurrency = 'BRL';
    public $selectedLanguage = 'br';
    public $selectedPlan = 'monthly';
    public $availableLanguages = [
        'br' => '🇧🇷 Português',
        'en' => '🇺🇸 English',
        'es' => '🇪🇸 Español',
    ];

    public $currencies = [
        'BRL' => ['symbol' => 'R$', 'name' => 'Real Brasileiro', 'code' => 'BRL', 'label' => "payment.brl"],
        'USD' => ['symbol' => '$', 'name' => 'Dólar Americano', 'code' => 'USD', 'label' => "payment.usd"],
        'EUR' => ['symbol' => '€', 'name' => 'Euro', 'code' => 'EUR', 'label' => "payment.eur"],
    ];

    public $bumpActive = false;
    public $bumps = [
        // [
        //     'id' => 4,
        //     'title' => 'Criptografía anónima',
        //     'description' => 'Acesso a conteúdos ao vivo e eventos',
        //     'price' => 9.99,
        //     'hash' => '3nidg2uzc0',
        //     'active' => false,
        // ],
        // [
        //     'id' => 5,
        //     'title' => 'Guia Premium',
        //     'description' => 'Acesso ao guia completo de estratégias',
        //     'price' => 14.99,
        //     'hash' => '7fjk3ldw0',
        //     'active' => false,
        // ],
    ];

    public $countdownMinutes = 14;
    public $countdownSeconds = 22;
    public $spotsLeft = 12;
    public $activityCount = 0;
    public $totals = [];
    public $benefits = [
        ['title' => 'Vídeos premium', 'description' => 'Acesso a todo nosso conteúdo sem restrições'],
        ['title' => 'Conteúdos diários', 'description' => 'Novas atualizações todos os dias'],
        ['title' => 'Sem anúncios', 'description' => 'Experiência limpa e sem interrupções'],
        ['title' => 'Personalização', 'description' => 'Configure sua conta como preferir'],
        ['title' => 'Atualizações semanais', 'description' => 'Novas funcionalidades toda semana'],
        ['title' => 'Votação e sugestões', 'description' => 'Ajude a moldar o futuro da plataforma']
    ];

    private PaymentGatewayInterface $paymentGateway;
    public $gateway;
    protected $apiUrl;
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'verify' => !env('APP_DEBUG'),
        ]);
        $this->apiUrl = config('services.streamit.api_url');
        $this->gateway = config('services.default_payment_gateway');
    }

    protected function rules()
    {
        $rules = [
            'cardName' => 'required|string|max:255',
            'cardNumber' => 'required|numeric|digits_between:13,19',
            'cardExpiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
            'cardCvv' => 'required|numeric|digits_between:3,4',
            'email' => 'required|email',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
        ];
        
        if ($this->selectedCurrency === 'BRL') {
            $rules['cpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
        }
        
        return $rules;
    }

    public function debug()
    {
        $this->cardName = 'João da Silva';
        $this->cardNumber = '4242424242424242';
        $this->cardExpiry = '12/25';
        $this->cardCvv = '123';
        $this->email = 'test@mail.com';
        $this->phone = '+5511999999999';
        $this->cpf = '123.456.789-09';
        $this->paymentMethodId = 'pm_1S5yVwIVhGS3bBwFlcYLzD5X';
    }

    public function mount(PaymentGatewayInterface $paymentGateway = null)
    {
        if (env('APP_DEBUG')) {
            $this->debug();
        }

        $this->plans = $this->getPlans();
        $this->selectedPlan = array_key_first($this->plans);
        dump($this->plans, array_key_first($this->plans));
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();
        $this->calculateTotals();
        $this->activityCount = rand(1, 50);
        
        // Tratamento seguro para o product
        $this->product = [
            'hash' => $this->getPlanHash($this->selectedPlan),
            'title' => $this->getPlanLabel($this->selectedPlan),
            'price_id' => $this->getPlanPriceId($this->selectedPlan, $this->selectedCurrency),
        ];
    }

    /**
     * Obtém o hash do plano de forma segura
     */
    private function getPlanHash($planKey)
    {
        return $this->plans[$planKey]['hash'] ?? 'default-hash';
    }

    /**
     * Obtém o label do plano de forma segura
     */
    private function getPlanLabel($planKey)
    {
        return $this->plans[$planKey]['label'] ?? __('payment.' . $planKey);
    }

    /**
     * Obtém o price_id do plano de forma segura
     */
    private function getPlanPriceId($planKey, $currency)
    {
        return $this->plans[$planKey]['prices'][$currency]['id'] ?? null;
    }

    public function getPlans()
    {
        try {
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $request = new Request(
                'GET',
                $this->apiUrl . '/get-plans',
                $headers
            );

            return $this->httpClient->sendAsync($request)
                ->then(function ($res) {
                    $responseBody = $res->getBody()->getContents();
                    $dataResponse = json_decode($responseBody, true);

                    $this->paymentGateway = PaymentGatewayFactory::create();
                    $result = $this->paymentGateway->formatPlans($dataResponse, $this->selectedCurrency);
                    
                    // Tratamento seguro para bumps
                    if (isset($result[$this->selectedPlan]['order_bumps'])) {
                        $this->bumps = $result[$this->selectedPlan]['order_bumps'];
                    }
                    
                    return $result;
                })
                ->otherwise(function ($e) {
                    \Log::channel('GetPlans')->error('PagePay: Error fetching plans from streamit.', [
                        'gateway' => $this->gateway,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Retorna estrutura padrão em caso de erro
                    return $this->getDefaultPlans();
                })
                ->wait();

        } catch (\Exception $e) {
            \Log::channel('GetPlans')->error('PagePay: Exception in getPlans method.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultPlans();
        }
    }

    /**
     * Retorna estrutura padrão de planos em caso de erro
     */
    private function getDefaultPlans()
    {
        return [
            'monthly' => [
                'hash' => 'default-monthly-hash',
                'label' => __('payment.monthly'),
                'nunber_months' => 1,
                'prices' => [
                    'BRL' => ['origin_price' => 50.00, 'descont_price' => 39.90, 'id' => null],
                    'USD' => ['origin_price' => 10.00, 'descont_price' => 7.90, 'id' => null],
                    'EUR' => ['origin_price' => 9.00, 'descont_price' => 6.90, 'id' => null],
                ],
                'order_bumps' => $this->bumps
            ],
            'quarterly' => [
                'hash' => 'default-quarterly-hash',
                'label' => __('payment.quarterly'),
                'nunber_months' => 3,
                'prices' => [
                    'BRL' => ['origin_price' => 150.00, 'descont_price' => 109.90, 'id' => null],
                    'USD' => ['origin_price' => 30.00, 'descont_price' => 21.90, 'id' => null],
                    'EUR' => ['origin_price' => 27.00, 'descont_price' => 19.90, 'id' => null],
                ],
                'order_bumps' => $this->bumps
            ],
            'semi-annual' => [
                'hash' => 'default-semiannual-hash',
                'label' => __('payment.semi-annual'),
                'nunber_months' => 6,
                'prices' => [
                    'BRL' => ['origin_price' => 300.00, 'descont_price' => 199.90, 'id' => null],
                    'USD' => ['origin_price' => 60.00, 'descont_price' => 39.90, 'id' => null],
                    'EUR' => ['origin_price' => 54.00, 'descont_price' => 35.90, 'id' => null],
                ],
                'order_bumps' => $this->bumps
            ]
        ];
    }

    /**
     * Calcula totais com tratamento de erros robusto
     */
    public function calculateTotals()
    {
        try {
            // Verifica se o plano selecionado existe
            if (!isset($this->plans[$this->selectedPlan])) {
                $this->selectedPlan = $this->getAvailablePlan();
            }

            $plan = $this->plans[$this->selectedPlan];
            
            // Verifica se a moeda selecionada está disponível
            if (!isset($plan['prices'][$this->selectedCurrency])) {
                $this->selectedCurrency = $this->getAvailableCurrency($plan);
            }

            $prices = $plan['prices'][$this->selectedCurrency];
            $numberMonths = $plan['nunber_months'] ?? 1;

            // Valores padrão para evitar erros
            $originPrice = $prices['origin_price'] ?? 0;
            $discountPrice = $prices['descont_price'] ?? 0;

            $this->totals = [
                'month_price' => $numberMonths > 0 ? $originPrice / $numberMonths : $originPrice,
                'month_price_discount' => $numberMonths > 0 ? $discountPrice / $numberMonths : $discountPrice,
                'total_price' => $originPrice,
                'total_discount' => max(0, $originPrice - $discountPrice),
            ];

            $finalPrice = $discountPrice;

            // Soma todos bumps ativos com tratamento seguro
            foreach ($this->bumps as $bump) {
                if (!empty($bump['active']) && isset($bump['price'])) {
                    $finalPrice += floatval($bump['price']);
                }
            }

            $this->totals['final_price'] = max(0, $finalPrice);

            // Formata os valores
            $this->totals = array_map(function ($value) {
                return is_numeric($value) ? number_format(round($value, 2), 2, ',', '.') : '0,00';
            }, $this->totals);

        } catch (\Exception $e) {
            \Log::channel('PaymentCalc')->error('PagePay: Error calculating totals.', [
                'selectedPlan' => $this->selectedPlan,
                'selectedCurrency' => $this->selectedCurrency,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Valores padrão em caso de erro
            $this->totals = [
                'month_price' => '0,00',
                'month_price_discount' => '0,00',
                'total_price' => '0,00',
                'total_discount' => '0,00',
                'final_price' => '0,00'
            ];
        }
    }

    /**
     * Obtém um plano disponível caso o selecionado não exista
     */
    private function getAvailablePlan()
    {
        $availablePlans = ['monthly', 'quarterly', 'semi-annual'];
        
        foreach ($availablePlans as $plan) {
            if (isset($this->plans[$plan])) {
                return $plan;
            }
        }
        
        return 'monthly';
    }

    /**
     * Obtém uma moeda disponível para o plano
     */
    private function getAvailableCurrency($plan)
    {
        $availableCurrencies = ['BRL', 'USD', 'EUR'];
        
        foreach ($availableCurrencies as $currency) {
            if (isset($plan['prices'][$currency])) {
                return $currency;
            }
        }
        
        return 'BRL';
    }

    /**
     * Atualiza os bumps para usar a mesma currency do plano principal
     */
    private function syncBumpsCurrency()
    {
        $plan = $this->plans[$this->selectedPlan] ?? [];
        $availablePrices = $plan['prices'] ?? [];
        
        foreach ($this->bumps as &$bump) {
            // Se o bump não tem preço na moeda selecionada, tenta usar a mesma lógica do plano
            if (!isset($bump['prices']) || !isset($bump['prices'][$this->selectedCurrency])) {
                // Mantém o preço original se não houver conversão disponível
                if (!isset($bump['price'])) {
                    $bump['price'] = 0;
                }
                
                // Se availablePrices tem a moeda, podemos tentar alguma conversão
                // ou manter o preço padrão do bump
                if (isset($availablePrices[$this->selectedCurrency])) {
                    // Aqui você pode adicionar lógica de conversão se necessário
                    // Por enquanto, mantém o preço original
                }
            } else {
                // Se o bump tem preços específicos por moeda
                $bump['price'] = $bump['prices'][$this->selectedCurrency]['descont_price'] ?? 
                                $bump['prices'][$this->selectedCurrency]['origin_price'] ?? 
                                $bump['price'] ?? 0;
            }
        }
    }

    public function startCheckout()
    {
        try {
            // Limpeza dos dados
            if ($this->cardNumber) {
                $this->cardNumber = preg_replace('/\D/', '', $this->cardNumber);
            }
            if ($this->cardCvv) {
                $this->cardCvv = preg_replace('/\D/', '', $this->cardCvv);
            }
            if ($this->phone) {
                $this->phone = preg_replace('/[^0-9+]/', '', $this->phone);
            }
            if ($this->cpf && $this->selectedCurrency === 'BRL') {
                $cpf = preg_replace('/\D/', '', $this->cpf);
                if (strlen($cpf) == 11) {
                    $this->cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . 
                                substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                }
            }

            $this->validate();
            $this->showSecure = true;
            $this->showLodingModal = true;

            // Sincroniza a currency dos bumps antes do checkout
            $this->syncBumpsCurrency();

            switch ($this->selectedPlan) {
                case 'monthly':
                case 'quarterly':
                    if (isset($this->plans['semi-annual'])) {
                        $this->prepareUpsellModal();
                        break;
                    }
                    // Continua para o default se não houver semi-annual

                default:
                    $this->sendCheckout();
                    $this->showLodingModal = false;
                    return;
            }

            $this->showLodingModal = false;

        } catch (\Exception $e) {
            \Log::channel('PaymentCheckout')->error('PagePay: Error in startCheckout.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->showLodingModal = false;
            $this->addError('payment', 'Erro ao processar checkout. Tente novamente.');
        }
    }

    /**
     * Prepara modal de upsell com tratamento seguro
     */
    private function prepareUpsellModal()
    {
        try {
            $semiAnnualPlan = $this->plans['semi-annual'];
            $currentPlan = $this->plans[$this->selectedPlan];

            if (!isset($semiAnnualPlan['prices'][$this->selectedCurrency]) || 
                !isset($currentPlan['prices'][$this->selectedCurrency])) {
                throw new \Exception('Price information not available for selected currency');
            }

            $offerValue = round(
                $semiAnnualPlan['prices'][$this->selectedCurrency]['descont_price'] / 
                ($semiAnnualPlan['nunber_months'] ?? 6),
                1
            );

            $offerDiscount = (
                $currentPlan['prices'][$this->selectedCurrency]['origin_price'] *
                ($semiAnnualPlan['nunber_months'] ?? 6)
            ) - ($offerValue * ($semiAnnualPlan['nunber_months'] ?? 6));

            $this->modalData = [
                'actual_month_value' => $this->totals['month_price_discount'] ?? '0,00',
                'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                'offer_total_discount' => number_format(max(0, $offerDiscount), 2, ',', '.'),
                'offer_total_value' => number_format(
                    $semiAnnualPlan['prices'][$this->selectedCurrency]['descont_price'],
                    2,
                    ',',
                    '.'
                ),
            ];

            $this->showUpsellModal = true;

        } catch (\Exception $e) {
            \Log::channel('PaymentUpsell')->error('PagePay: Error preparing upsell modal.', [
                'error' => $e->getMessage()
            ]);
            
            // Em caso de erro, vai direto para o checkout
            $this->sendCheckout();
        }
    }

    public function rejectUpsell()
    {
        $this->showUpsellModal = false;
        
        try {
            if ($this->selectedPlan === 'monthly' && isset($this->plans['quarterly'])) {
                $this->prepareDownsellModal();
            } else {
                $this->sendCheckout();
            }
        } catch (\Exception $e) {
            \Log::channel('PaymentUpsell')->error('PagePay: Error in rejectUpsell.', [
                'error' => $e->getMessage()
            ]);
            $this->sendCheckout();
        }
    }

    /**
     * Prepara modal de downsell com tratamento seguro
     */
    private function prepareDownsellModal()
    {
        try {
            $quarterlyPlan = $this->plans['quarterly'];
            $monthlyPlan = $this->plans['monthly'];

            if (!isset($quarterlyPlan['prices'][$this->selectedCurrency]) || 
                !isset($monthlyPlan['prices'][$this->selectedCurrency])) {
                throw new \Exception('Price information not available for selected currency');
            }

            $offerValue = round(
                $quarterlyPlan['prices'][$this->selectedCurrency]['descont_price'] / 
                ($quarterlyPlan['nunber_months'] ?? 3),
                1
            );

            $basePrice = $monthlyPlan['prices'][$this->selectedCurrency]['origin_price'];
            $offerDiscount = ($basePrice * ($quarterlyPlan['nunber_months'] ?? 3)) - 
                           ($offerValue * ($quarterlyPlan['nunber_months'] ?? 3));

            $this->modalData = [
                'actual_month_value' => $this->totals['month_price_discount'] ?? '0,00',
                'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                'offer_total_discount' => number_format(max(0, $offerDiscount), 2, ',', '.'),
                'offer_total_value' => number_format(
                    $quarterlyPlan['prices'][$this->selectedCurrency]['descont_price'],
                    2,
                    ',',
                    '.'
                ),
            ];

            $this->showDownsellModal = true;

        } catch (\Exception $e) {
            \Log::channel('PaymentDownsell')->error('PagePay: Error preparing downsell modal.', [
                'error' => $e->getMessage()
            ]);
            
            $this->sendCheckout();
        }
    }

    public function acceptUpsell()
    {
        $this->selectedPlan = 'semi-annual';
        $this->calculateTotals();
        $this->showUpsellModal = false;
        $this->sendCheckout();
    }

    public function sendCheckout()
    {
        try {
            $this->showDownsellModal = $this->showUpsellModal = false;
            $this->showProcessingModal = true;

            $checkoutData = $this->prepareCheckoutData();
            $this->paymentGateway = PaymentGatewayFactory::create();

            $response = $this->paymentGateway->processPayment($checkoutData);

            $this->showProcessingModal = false;

            if ($response['status'] === 'success') {
                Log::channel('payment_checkout')->info('PagePay: Payment successful via gateway.', [
                    'gateway' => get_class($this->paymentGateway),
                    'response' => $response
                ]);

                if (isset($response['data']) && !empty($response['data'])) {
                    $customerId = $response['data']['customerId'] ?? '';
                    $redirectUrl = $response['data']['redirect_url'] ?? 'https://web.snaphubb.online/obg/';
                    $upsell_productId = $response['data']['upsell_productId'] ?? '';
                    
                    return redirect()->to($redirectUrl . "?customerId=" . $customerId . "&upsell_productId=" . $upsell_productId);
                }
                
                $redirectUrl = $response['redirect_url'] ?? "https://web.snaphubb.online/obg/";
                return redirect()->to($redirectUrl);
                
            } else {
                Log::channel('payment_checkout')->error('PagePay: Payment failed via gateway.', [
                    'gateway' => get_class($this->paymentGateway),
                    'response' => $response
                ]);
                
                $errorMessage = $response['message'] ?? 'An unknown error occurred during payment.';
                if (!empty($response['errors'])) {
                    $errorMessage .= ' Details: ' . implode(', ', (array)$response['errors']);
                }
                
                $this->addError('payment', $errorMessage);
                $this->showProcessingModal = false;
            }

        } catch (\Exception $e) {
            \Log::channel('PaymentCheckout')->error('PagePay: Error in sendCheckout.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->showProcessingModal = false;
            $this->addError('payment', 'Erro crítico ao processar pagamento. Tente novamente.');
        }
    }

    private function prepareCheckoutData()
    {
        try {
            $numeric_final_price = 0;
            if (isset($this->totals['final_price'])) {
                $numeric_final_price = floatval(str_replace(',', '.', str_replace('.', '', $this->totals['final_price'])));
            }

            $expMonth = null;
            $expYear = null;
            if ($this->cardExpiry) {
                $parts = explode('/', $this->cardExpiry);
                $expMonth = $parts[0] ?? null;
                if (!empty($parts[1])) {
                    $expYear = (strlen($parts[1]) == 2) ? '20' . $parts[1] : $parts[1];
                }
            }

            $cartItems = [];
            $currentPlanDetails = $this->plans[$this->selectedPlan] ?? [];
            $currentPlanPriceInfo = $currentPlanDetails['prices'][$this->selectedCurrency] ?? [];

            // Plano principal
            if (!empty($currentPlanDetails)) {
                $cartItems[] = [
                    'product_hash' => $currentPlanDetails['hash'] ?? 'default-hash',
                    'title' => ($this->product['title'] ?? 'Produto') . ' - ' . ($currentPlanDetails['label'] ?? 'Plano'),
                    'price' => (int)round(($currentPlanPriceInfo['descont_price'] ?? 0) * 100),
                    'price_id' => $this->product['price_id'] ?? null,
                    'recurring' => $currentPlanPriceInfo['recurring'] ?? null,
                    'quantity' => 1,
                    'operation_type' => 1,
                ];
            }

            // Bumps ativos
            foreach ($this->bumps as $bump) {
                if (!empty($bump['active'])) {
                    $cartItems[] = [
                        'product_hash' => $bump['hash'] ?? 'bump-default-hash',
                        'price_id' => $bump['price_id'] ?? null,
                        'title' => $bump['title'] ?? 'Bump Product',
                        'price' => (int)round(($bump['price'] ?? 0) * 100),
                        'recurring' => $bump['recurring'] ?? null,
                        'quantity' => 1,
                        'operation_type' => 2,
                    ];
                }
            }

            // Customer data
            $customerData = [
                'name' => $this->cardName ?? '',
                'email' => $this->email ?? '',
                'phone_number' => preg_replace('/[^0-9+]/', '', $this->phone ?? ''),
            ];
            
            if ($this->selectedCurrency === 'BRL' && $this->cpf) {
                $customerData['document'] = preg_replace('/\D/', '', $this->cpf);
            }

            $cardDetails = [
                'number' => $this->cardNumber ?? '',
                'holder_name' => $this->cardName ?? '',
                'exp_month' => $expMonth,
                'exp_year' => $expYear,
                'cvv' => $this->cardCvv ?? '',
            ];
            
            if ($this->selectedCurrency === 'BRL' && $this->cpf) {
                $cardDetails['document'] = preg_replace('/\D/', '', $this->cpf);
            }

            return [
                'amount' => (int)round($numeric_final_price * 100),
                'currency_code' => $this->selectedCurrency,
                'offer_hash' => $currentPlanDetails['hash'] ?? 'default-hash',
                'upsell_url' => $currentPlanDetails['upsell_url'] ?? null,
                'payment_method' => 'credit_card',
                'payment_method_id' => $this->paymentMethodId,
                'card' => $cardDetails,
                'customer' => $customerData,
                'cart' => $cartItems,
                'installments' => 1,
                'selected_plan_key' => $this->selectedPlan,
                'language' => $this->selectedLanguage,
                'metadata' => [
                    'product_main_hash' => $this->product['hash'] ?? 'default-hash',
                    'bumps_selected' => collect($this->bumps)->where('active', true)->pluck('id')->toArray(),
                ]
            ];

        } catch (\Exception $e) {
            \Log::channel('PaymentData')->error('PagePay: Error preparing checkout data.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retorna estrutura mínima em caso de erro
            return [
                'amount' => 0,
                'currency_code' => $this->selectedCurrency,
                'offer_hash' => 'error-hash',
                'payment_method' => 'credit_card',
                'card' => [],
                'customer' => [],
                'cart' => [],
                'installments' => 1,
                'selected_plan_key' => $this->selectedPlan,
                'language' => $this->selectedLanguage,
                'metadata' => []
            ];
        }
    }

    // Restante dos métodos mantidos com pequenos ajustes...

    public function decrementTimer()
    {
        if ($this->countdownSeconds > 0) {
            $this->countdownSeconds--;
        } elseif ($this->countdownMinutes > 0) {
            $this->countdownSeconds = 59;
            $this->countdownMinutes--;
        }
    }

    public function acceptDownsell()
    {
        $this->selectedPlan = 'quarterly';
        $this->calculateTotals();
        $this->showDownsellModal = false;
        $this->sendCheckout();
    }

    public function rejectDownsell()
    {
        $this->showDownsellModal = false;
        $this->sendCheckout();
    }

    public function getListeners()
    {
        return [];
    }

    public function updateActivityCount()
    {
        $this->activityCount = rand(1, 50);
    }

    public function changeLanguage($lang)
    {
        try {
            if (array_key_exists($lang, $this->availableLanguages)) {
                session(['locale' => $lang]);
                app()->setLocale($lang);
                $this->selectedLanguage = $lang;
                $this->selectedCurrency = $lang === 'br' ? 'BRL' : ($lang === 'en' ? 'USD' : ($lang === 'es' ? 'EUR' : 'BRL'));
                
                $this->plans = $this->getPlans();
                $this->calculateTotals();
                $this->dispatch('languageChanged');
            }
        } catch (\Exception $e) {
            \Log::channel('PaymentLanguage')->error('PagePay: Error changing language.', [
                'language' => $lang,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function decrementSpotsLeft()
    {
        if (rand(1, 5) == 1 && $this->spotsLeft > 3) {
            $this->spotsLeft--;
            $this->dispatch('spots-updated');
        }
    }

    public function updateLiveActivity()
    {
        $this->activityCount = rand(3, 25);
        $this->dispatch('activity-updated');
    }

    public function render()
    {
        return view('livewire.page-pay')->layoutData([
            'title' => __('payment.title'),
            'canonical' => url()->current(),
        ]);
    }
}