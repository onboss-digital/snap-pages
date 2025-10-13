<?php

namespace App\Livewire;

use App\Factories\PaymentGatewayFactory; // Added
use App\Interfaces\PaymentGatewayInterface; // Added
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PagePay extends Component
{

    public $paymentMethodId, $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone, $cpf,
        $plans, $modalData, $product, $testimonials = [],
        $utm_source, $utm_medium, $utm_campaign, $utm_id, $utm_term, $utm_content;

    // ===== NOVAS PROPRIEDADES PARA PIX =====
    public $selectedPaymentMethod = 'credit_card'; // 'credit_card' ou 'pix'
    public $pixData = null; // Dados do PIX (QR Code, brCode, etc)
    public $pixStatus = null; // Status do pagamento PIX (PENDING, PAID, EXPIRED, FAILED)

    // Modais
    public $showSuccessModal = false;
    public $showErrorModal = false;
    public $showSecure = false;
    public $showLodingModal = false; // Note: "Loding" might be a typo for "Loading"
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
        [
            'id' => 4,
            'title' => 'Criptografía anónima',
            'description' => 'Acesso a conteúdos ao vivo e eventos',
            'price' => 9.99,
            'hash' => '3nidg2uzc0',
            'active' => false,
        ],
        [
            'id' => 5,
            'title' => 'Guia Premium',
            'description' => 'Acesso ao guia completo de estratégias',
            'price' => 14.99,
            'hash' => '7fjk3ldw0',
            'active' => false,
        ],
    ];

    public $countdownMinutes = 14;
    public $countdownSeconds = 22;
    public $spotsLeft = 12;
    public $activityCount = 0;
    public $totals = [];

    private PaymentGatewayInterface $paymentGateway; // Added

    public $gateway;
    protected $apiUrl;
    private $httpClient;
    public function __construct()
    {
        $this->httpClient = new Client([
            'verify' => !env('APP_DEBUG'), // <- ignora verificação de certificado SSL
        ]);
        $this->apiUrl = config('services.streamit.api_url'); // Assuming you'll store the API URL in config
        $this->gateway = config('services.default_payment_gateway');
    }

    protected function rules()
    {
        $rules = [
            'cardName' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
        ];

        if ($this->selectedLanguage === 'br') {
            $rules['cpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
        }

        if ($this->gateway !== 'stripe') {
            $rules['cardNumber'] = 'required|numeric|digits_between:13,19';
            $rules['cardExpiry'] = ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'];
            $rules['cardCvv'] = 'required|numeric|digits_between:3,4';
        }

        return $rules;
    }

    public function debug()
    {
        $this->cardName = 'João da Silva';
        $this->cardNumber = '4242424242424242'; // Example Visa card number
        $this->cardExpiry = '12/25'; // Example expiry date
        $this->cardCvv = '123'; // Example CVV
        $this->email = 'test@mail.com';
        $this->phone = '+5511999999999'; // Example phone number
        $this->cpf = '123.456.789-09'; // Example CPF, valid format        
        $this->paymentMethodId = 'pm_1SBQpKIVhGS3bBwFk2Idz2kp'; //'pm_1S5yVwIVhGS3bBwFlcYLzD5X'; //adicione um metodo de pagamento pra testar capture no elements do stripe
    }

    public function mount(PaymentGatewayInterface $paymentGateway = null) // Modified to allow injection, or resolve via factory
    {
        $this->utm_source = request()->query('utm_source');
        $this->utm_medium = request()->query('utm_medium');
        $this->utm_campaign = request()->query('utm_campaign');
        $this->utm_id = request()->query('utm_id');
        $this->utm_term = request()->query('utm_term');
        $this->utm_content = request()->query('utm_content');

        if (env('APP_DEBUG')) {
            $this->debug();
        }

        if (!Session::has('locale_detected')) {
            $this->detectLanguage();
            Session::put('locale_detected', true);
        } else {
            $this->selectedLanguage = session('locale', 'br');
            app()->setLocale($this->selectedLanguage);
        }

        $this->testimonials = trans('checkout.testimonials');
        $this->plans = $this->getPlans();
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->calculateTotals();
        $this->activityCount = rand(1, 50);

        if (isset($this->plans[$this->selectedPlan])) {
            $this->product = [
                'hash' => $this->plans[$this->selectedPlan]['hash'] ?? null,
                'title' => $this->plans[$this->selectedPlan]['label'] ?? '',
                'price_id' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['id'] ?? null,
            ];
        } else {
            $this->product = [
                'hash' => null,
                'title' => '',
                'price_id' => null,
            ];
        }
    }

    public function getPlans()
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        $request = new Request(
            'GET',
            $this->apiUrl . '/get-plans',
            $headers,
        );
        return $this->httpClient->sendAsync($request)
            ->then(function ($res) {
                $responseBody = $res->getBody()->getContents();
                $dataResponse = json_decode($responseBody, true);
                $this->paymentGateway = PaymentGatewayFactory::create();
                $result = $this->paymentGateway->formatPlans($dataResponse, $this->selectedCurrency);
                $this->bumps = $result[$this->selectedPlan]['order_bumps'];
                return $result;
            })
            ->otherwise(function ($e) {
                \Log::channel('GetPlans')->info('PagePay: GetPlans from streamit.', [
                    'gateway' => $this->gateway,
                    'error' => $e->getMessage(),
                ]);
                return [];
            })
            ->wait();
    }

    public function calculateTotals()
{
    if (!isset($this->plans[$this->selectedPlan])) {
        Log::error('Plano selecionado não encontrado na resposta da API.', [
            'selected_plan' => $this->selectedPlan
        ]);
        return;
    }
    $plan = $this->plans[$this->selectedPlan];

    if (!isset($plan['prices']) || !is_array($plan['prices'])) {
        Log::error('Array de preços não encontrado para o plano.', [
            'plan' => $this->selectedPlan
        ]);
        return;
    }

    $availableCurrency = null;
    if (isset($plan['prices'][$this->selectedCurrency])) {
        $availableCurrency = $this->selectedCurrency;
    } elseif (isset($plan['prices']['BRL'])) {
        $availableCurrency = 'BRL';
    } elseif (isset($plan['prices']['USD'])) {
        $availableCurrency = 'USD';
    }
    
    if (is_null($availableCurrency)) {
        Log::error('Nenhuma moeda válida (BRL, USD, etc.) encontrada para o plano.', [
            'plan' => $this->selectedPlan
        ]);
        $this->addError('totals', 'Não foi possível carregar os preços. Tente novamente mais tarde.');
        return;
    }

    $this->selectedCurrency = $availableCurrency;
    $prices = $plan['prices'][$this->selectedCurrency];

    $this->totals = [
        'month_price' => $prices['origin_price'] / $plan['nunber_months'],
        'month_price_discount' => $prices['descont_price'] / $plan['nunber_months'],
        'total_price' => $prices['origin_price'],
        'total_discount' => $prices['origin_price'] - $prices['descont_price'],
    ];

    $finalPrice = $prices['descont_price'];

    foreach ($this->bumps as $bump) {
        if (!empty($bump['active'])) {
            $finalPrice += floatval($bump['price']);
        }
    }

    $this->totals['final_price'] = $finalPrice;

    $this->totals = array_map(function ($value) {
        return number_format(round($value, 2), 2, ',', '.');
    }, $this->totals);
}


    public function startCheckout()
    {
        if ($this->cardNumber) {
            $this->cardNumber = preg_replace('/\D/', '', $this->cardNumber);
        }
        if ($this->cardCvv) {
            $this->cardCvv = preg_replace('/\D/', '', $this->cardCvv);
        }
        if ($this->phone) {
            $this->phone = preg_replace('/[^0-9+]/', '', $this->phone);
        }
        if ($this->cpf && $this->selectedLanguage === 'br') {
            $cpf = preg_replace('/\D/', '', $this->cpf);
            if (strlen($cpf) == 11) {
                $this->cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
            }
        }

        try {
            $rules = $this->rules();
            $this->validate($rules);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation:failed');
            throw $e;
        }

        try {
            $this->showSecure = true;
            $this->showLodingModal = true;

            // ===== FLUXO PIX: SEM UPSELL/DOWNSELL =====
            if ($this->selectedPaymentMethod === 'pix') {
                $this->showProcessingModal = true;
                $this->sendCheckout();
                $this->showLodingModal = false;
                return;
            }

            // ===== FLUXO CARTÃO: COM UPSELL/DOWNSELL (ORIGINAL) =====
            switch ($this->selectedPlan) {
                case 'monthly':
                case 'quarterly':
                    if (isset($this->plans['semi-annual'])) {
                        $offerValue = round(
                            $this->plans['semi-annual']['prices'][$this->selectedCurrency]['descont_price']
                                / $this->plans['semi-annual']['nunber_months'],
                            1
                        );

                        $offerDiscont = (
                            $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['origin_price']
                            * $this->plans['semi-annual']['nunber_months']
                        ) - ($offerValue * $this->plans['semi-annual']['nunber_months']);

                        $this->modalData = [
                            'actual_month_value'    => $this->totals['month_price_discount'],
                            'offer_month_value'     => number_format($offerValue, 2, ',', '.'),
                            'offer_total_discount'  => number_format($offerDiscont, 2, ',', '.'),
                            'offer_total_value'     => number_format(
                                $this->plans['semi-annual']['prices'][$this->selectedCurrency]['descont_price'],
                                2,
                                ',',
                                '.'
                            ),
                        ];
                        break;
                    }
                default:
                    $this->showProcessingModal = true;
                    $this->sendCheckout();
                    $this->showLodingModal = false;
                    return;
            }
        } catch (\Exception $e) {
            Log::channel('start_checkout')->error('start_checkout: API Error:', [
                'message' => $e->getMessage(),
            ]);
        }

        $this->showLodingModal = false;
    }

    public function rejectUpsell()
    {
        $this->showUpsellModal = false;
        if ($this->selectedPlan === 'monthly') {
            $offerValue = round($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['quarterly']['nunber_months'], 1);
            $basePriceForDiscountCalc = $this->plans['monthly']['prices'][$this->selectedCurrency]['origin_price'];
            $offerDiscont = ($basePriceForDiscountCalc * $this->plans['quarterly']['nunber_months']) - ($offerValue * $this->plans['quarterly']['nunber_months']);

            $this->modalData = [
                'actual_month_value' => $this->totals['month_price_discount'],
                'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                'offer_total_discount' => number_format(abs($offerDiscont), 2, ',', '.'),
                'offer_total_value' => number_format($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'], 2, ',', '.'),
            ];
            $this->showDownsellModal = true;
        } else {
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
        $checkoutData = $this->prepareCheckoutData();
        $this->paymentGateway = PaymentGatewayFactory::create();
        $response = $this->paymentGateway->processPayment($checkoutData);

        // ===== FLUXO PIX =====
        if ($this->selectedPaymentMethod === 'pix') {
            if ($response['status'] === 'success') {
                $this->pixData = $response['data'];
                $this->pixStatus = $response['data']['status'] ?? 'PENDING';
                $this->showProcessingModal = false;
                
                Log::channel('payment_checkout')->info('PIX criado', [
                    'pix_id' => $this->pixData['pix_id'],
                ]);
            } else {
                $this->showProcessingModal = false;
                $this->showErrorModal = true;
                $this->modalData = [
                    'message' => $response['message'] ?? 'Erro ao gerar PIX.',
                ];
            }
            return;
        }

        // ===== FLUXO CARTÃO (ORIGINAL) =====
        if ($response['status'] === 'success') {
            Log::channel('payment_checkout')->info('PagePay: Payment successful via gateway.', [
                'gateway' => get_class($this->paymentGateway),
                'response' => $response
            ]);

            $this->showSuccessModal = true;
            $this->showProcessingModal = false;
            $this->showErrorModal = false;

            $purchaseData = [
                'transaction_id' => $response['transaction_id'] ?? $response['data']['transaction_id'] ?? null,
                'value' => $checkoutData['amount'] / 100,
                'currency' => $checkoutData['currency_code'],
                'content_ids' => array_map(function ($item) {
                    return $item['product_hash'];
                }, $checkoutData['cart']),
                'content_type' => 'product',
            ];

            $this->dispatch('checkout-success', purchaseData: $purchaseData);

            if (isset($response['data']) && !empty($response['data'])) {
                $customerId = $response['data']['customerId'];
                $redirectUrl = $response['data']['redirect_url'];
                $upsell_productId = $response['data']['upsell_productId'];
                if (!empty($redirectUrl)) {
                return redirect()->to($redirectUrl . "?customerId=" . $customerId . "&upsell_productId=" . $upsell_productId);
                } else {
                    return;
                }
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
            $this->showErrorModal = true;
            $this->showProcessingModal = false;
        }
    }

    private function prepareCheckoutData()
    {
        $numeric_final_price = floatval(str_replace(',', '.', str_replace('.', '', $this->totals['final_price'])));

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
        $currentPlanDetails = $this->plans[$this->selectedPlan];
        $currentPlanPriceInfo = $currentPlanDetails['prices'][$this->selectedCurrency];

        $cartItems[] = [
            'product_hash' => $currentPlanDetails['hash'],
            'title' => $currentPlanDetails['label'],
            'price' => (int)round(floatval($currentPlanPriceInfo['descont_price']) * 100),
            'price_id' => $this->product['price_id'] ?? null,
            'recurring' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['recurring'] ?? null,
            'quantity' => 1,
            'operation_type' => 1,
        ];

        foreach ($this->bumps as $bump) {
            if (!empty($bump['active'])) {
                $cartItems[] = [
                    'product_hash' => $bump['hash'],
                    'price_id' => $bump['price_id'] ?? null,
                    'title' => $bump['title'],
                    'price' => (int)round(floatval($bump['price']) * 100),
                    'recurring' => $bump['recurring'] ?? null,
                    'quantity' => 1,
                    'operation_type' => 2,
                ];
            }
        }

        $customerData = [
            'name' => $this->cardName,
            'email' => $this->email,
            'phone_number' => preg_replace('/[^0-9+]/', '', $this->phone),
        ];
        if ($this->selectedLanguage === 'br' && $this->cpf) {
            $customerData['document'] = preg_replace('/\D/', '', $this->cpf);
        }

        $cardDetails = [
            'number' => $this->cardNumber,
            'holder_name' => $this->cardName,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvv' => $this->cardCvv,
        ];
        if ($this->selectedLanguage === 'br' && $this->cpf) {
            $cardDetails['document'] = preg_replace('/\D/', '', $this->cpf);
        }

        $baseData = [
            'amount' => (int)round($numeric_final_price * 100),
            'currency_code' => $this->selectedCurrency,
            'offer_hash' => $currentPlanDetails['hash'],
            'upsell_url' => $currentPlanDetails['upsell_url'] ?? null,
            'payment_method' => $this->selectedPaymentMethod,
            'customer' => $customerData,
            'cart' => $cartItems,
            'installments' => 1,
            'selected_plan_key' => $this->selectedPlan,
            'language' => $this->selectedLanguage,
            'metadata' => [
                'product_main_hash' => $this->product['hash'] ?? $currentPlanDetails['hash'],
                'bumps_selected' => collect($this->bumps)->where('active', true)->pluck('id')->toArray(),
                'utm_source' => $this->utm_source,
                'utm_medium' => $this->utm_medium,
                'utm_campaign' => $this->utm_campaign,
                'utm_id' => $this->utm_id,
                'utm_term' => $this->utm_term,
                'utm_content' => $this->utm_content,
            ]
        ];

        // ===== ADICIONAR DADOS ESPECÍFICOS DO MÉTODO DE PAGAMENTO =====
        if ($this->selectedPaymentMethod === 'credit_card') {
            $baseData['payment_method_id'] = $this->paymentMethodId;
            $baseData['card'] = $cardDetails;
        } elseif ($this->selectedPaymentMethod === 'pix') {
            $baseData['expiresIn'] = config('services.abacatepay.pix_expiration', 1800);
        }

        return $baseData;
    }

    public function closeModal()
    {
        $this->showErrorModal = false;
        $this->showSuccessModal = false;
    }

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
        if (array_key_exists($lang, $this->availableLanguages)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);
            $this->selectedLanguage = $lang;
            $this->selectedCurrency = $lang === 'br' ? 'BRL'
                : ($lang === 'en' ? 'USD'
                    : ($lang === 'es' ? 'EUR' : 'BRL'));
            
            // ===== RESETAR PARA CARTÃO SE NÃO FOR BRASIL =====
            if ($lang !== 'br' && $this->selectedPaymentMethod === 'pix') {
                $this->selectedPaymentMethod = 'credit_card';
                $this->pixData = null;
                $this->pixStatus = null;
            }
            
            // Recalculate plans and totals as language might affect labels (though prices should be language-agnostic)
            $this->plans = $this->getPlans(); // Re-fetch plans to update labels
            $this->testimonials = trans('checkout.testimonials');
            $this->calculateTotals();
            $this->dispatch('languageChanged');
        }
    }

    public function decrementSpotsLeft()
    {
        if (rand(1, 5) == 1) {
            if ($this->spotsLeft > 3) {
                $this->spotsLeft--;
                $this->dispatch('spots-updated');
            }
        }
    }

    public function updateLiveActivity()
    {
        $this->activityCount = rand(3, 25);
        $this->dispatch('activity-updated');
    }

    private function detectLanguage()
    {
        $preferredLanguage = request()->getPreferredLanguage(array_keys($this->availableLanguages));

        if (str_starts_with($preferredLanguage, 'pt')) {
            $this->selectedLanguage = 'br';
        } elseif (str_starts_with($preferredLanguage, 'es')) {
            $this->selectedLanguage = 'es';
        } else {
            $this->selectedLanguage = 'en';
        }

        $this->changeLanguage($this->selectedLanguage);
    }

    // ===== MÉTODO PARA VERIFICAR STATUS DO PIX (POLLING) =====
    public function checkPixStatus()
    {
        if (!$this->pixData || !isset($this->pixData['pix_id'])) {
            return;
        }

        try {
            $pixGateway = PaymentGatewayFactory::create('abacatepay');
            $response = $pixGateway->checkPaymentStatus($this->pixData['pix_id']);

            if ($response['status'] === 'success') {
                $this->pixStatus = $response['data']['status'];

                if ($this->pixStatus === 'PAID') {
                    // Redirecionar para página de obrigado
                    return redirect()->to('https://web.snaphubb.online/obg/');
                }
            }
        } catch (\Exception $e) {
            Log::error('Erro ao verificar status do PIX', [
                'pix_id' => $this->pixData['pix_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.page-pay')->layoutData([
            'title' => __('payment.title'),
            'canonical' => url()->current(),
        ]);
    }
}