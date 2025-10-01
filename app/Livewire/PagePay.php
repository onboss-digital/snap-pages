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
        $plans, $modalData, $product;

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
    public $benefits = [
        ['title' => 'Vídeos premium', 'description' => 'Acesso a todo nosso conteúdo sem restrições'],
        ['title' => 'Conteúdos diários', 'description' => 'Novas atualizações todos os dias'],
        ['title' => 'Sem anúncios', 'description' => 'Experiência limpa e sem interrupções'],
        ['title' => 'Personalização', 'description' => 'Configure sua conta como preferir'],
        ['title' => 'Atualizações semanais', 'description' => 'Novas funcionalidades toda semana'],
        ['title' => 'Votação e sugestões', 'description' => 'Ajude a moldar o futuro da plataforma']
    ];

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
        if (env('APP_DEBUG')) {
            $this->debug();
        }

        $this->plans = $this->getPlans();
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();
        $this->calculateTotals();
        $this->activityCount = rand(1, 50);

        if (!empty($this->plans) && isset($this->plans[$this->selectedPlan])) {
            $this->product = [
                'hash' => $this->plans[$this->selectedPlan]['hash'], // This hash might be gateway-specific
                'title' => $this->plans[$this->selectedPlan]['label'],
                'price_id' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['id'] ?? null,
            ];
        } else {
            // Fallback for product if plans are not loaded
            $this->product = [
                'hash' => 'default-product-hash',
                'title' => 'Default Product',
                'price_id' => null,
            ];
        }
        // $this->product = [
        //     'hash' => '8v1zcpkn9j', // This hash might be gateway-specific
        //     'title' => 'SNAPHUBB BR',
        //     'cover' => 'https://d2lr0gp42cdhn1.cloudfront.net/3564404929/products/ua11qf25qootxsznxicnfdbrd',
        //     'product_type' => 'digital',
        //     'guaranted_days' => 7,
        //     'sale_page' => 'https://snaphubb.online',
        // ];
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
                if (isset($result[$this->selectedPlan])) {
                    $this->bumps = $result[$this->selectedPlan]['order_bumps'];
                }
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

        // // Plan hashes might need to be gateway-specific or mapped
        // return \GuzzleHttp\Promise\promise_for([
        //     'monthly' => [
        //         'hash' => 'rwquocfj5c', // Gateway-specific?
        //         'label' => __('payment.monthly'),
        //         'nunber_months' => 1,
        //         'prices' => [
        //             'BRL' => ['origin_price' => 50.00, 'descont_price' => 39.90],
        //             'USD' => ['origin_price' => 10.00, 'descont_price' => 7.90],
        //             'EUR' => ['origin_price' => 9.00, 'descont_price' => 6.90],
        //         ],
        //     ],
        //     'quarterly' => [
        //         'hash' => 'velit nostrud dolor in deserunt', // Gateway-specific?
        //         'label' => __('payment.quarterly'),
        //         'nunber_months' => 3,
        //         'prices' => [
        //             'BRL' => ['origin_price' => 150.00, 'descont_price' => 109.90],
        //             'USD' => ['origin_price' => 30.00, 'descont_price' => 21.90],
        //             'EUR' => ['origin_price' => 27.00, 'descont_price' => 19.90],
        //         ],
        //     ],
        //     'semi-annual' => [
        //         'hash' => 'cupxl', // Gateway-specific?
        //         'label' => __('payment.semi-annual'),
        //         'nunber_months' => 6,
        //         'prices' => [
        //             'BRL' => ['origin_price' => 300.00, 'descont_price' => 199.90],
        //             'USD' => ['origin_price' => 60.00, 'descont_price' => 39.90],
        //             'EUR' => ['origin_price' => 54.00, 'descont_price' => 35.90],
        //         ],
        //     ]
        // ]);
        // Ensure getPlans returns the full structure as before
    }

    // calculateTotals, startCheckout, rejectUpsell, acceptUpsell remain largely the same
    // but sendCheckout and prepareCheckoutData will be modified.

    public function calculateTotals()
    {
        if (empty($this->plans) || !isset($this->plans[$this->selectedPlan])) {
            $this->totals = [
                'month_price' => '0,00', 'month_price_discount' => '0,00',
                'total_price' => '0,00', 'total_discount' => '0,00',
                'final_price' => '0,00',
            ];
            return;
        }

        $plan = $this->plans[$this->selectedPlan];

        if (empty($plan['prices'])) {
            $this->totals = [
                'month_price' => '0,00', 'month_price_discount' => '0,00',
                'total_price' => '0,00', 'total_discount' => '0,00',
                'final_price' => '0,00',
            ];
            return;
        }

        if (!isset($plan['prices'][$this->selectedCurrency])) {
            reset($plan['prices']);
            $this->selectedCurrency = key($plan['prices']);
        }

        $prices = $plan['prices'][$this->selectedCurrency];

        $this->totals = [
            'month_price' => $prices['origin_price'] / $plan['number_months'],
            'month_price_discount' => $prices['descont_price'] / $plan['number_months'],
            'total_price' => $prices['origin_price'],
            'total_discount' => $prices['origin_price'] - $prices['descont_price'],
        ];

        $finalPrice = $prices['descont_price'];

        // soma todos bumps ativos
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
        try {
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
                    $this->cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                }
            }
            $this->gateway === "stripe" ? null :  $this->validate();
            $this->showSecure = true;
            $this->showLodingModal = true; // Assuming "Loding" is intended

            switch ($this->selectedPlan) {
                case 'monthly':
                case 'quarterly':
                    if (isset($this->plans['semi-annual'])) {
                        //$this->showUpsellModal = true;

                        $offerValue = round(
                            $this->plans['semi-annual']['prices'][$this->selectedCurrency]['descont_price']
                                / $this->plans['semi-annual']['number_months'],
                            1
                        );

                        $offerDiscont = (
                            $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['origin_price']
                            * $this->plans['semi-annual']['number_months']
                        ) - ($offerValue * $this->plans['semi-annual']['number_months']);

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
                        break; // só interrompe se o semi-annual existir
                    }

                    // se não tem semi-annual, segue fluxo normal (igual default)
                    // não dá break aqui
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
        // Logic for downsell offer (quarterly)
        if ($this->selectedPlan === 'monthly') { // Only show downsell if current plan is monthly
            $offerValue = round($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['quarterly']['number_months'], 1);
            // Corrected discount calculation for downsell
            $basePriceForDiscountCalc = $this->plans['monthly']['prices'][$this->selectedCurrency]['origin_price']; // Price of the plan they *were* on
            $offerDiscont = ($basePriceForDiscountCalc * $this->plans['quarterly']['number_months']) - ($offerValue * $this->plans['quarterly']['number_months']);

            $this->modalData = [
                'actual_month_value' => $this->totals['month_price_discount'], // This should be from the current 'monthly' plan
                'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                'offer_total_discount' => number_format(abs($offerDiscont), 2, ',', '.'), // Ensure positive discount
                'offer_total_value' => number_format($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'], 2, ',', '.'),
            ];
            $this->showDownsellModal = true;
        } else { // If they were on quarterly and rejected upsell to semi-annual, just proceed with quarterly
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
        //$this->showDownsellModal = $this->showUpsellModal = false;        

        $checkoutData = $this->prepareCheckoutData();
        $this->paymentGateway = PaymentGatewayFactory::create();
        $response = $this->paymentGateway->processPayment($checkoutData);

        if ($response['status'] === 'success') {
            Log::channel('payment_checkout')->info('PagePay: Payment successful via gateway.', [
                'gateway' => get_class($this->paymentGateway),
                'response' => $response
            ]);

            $this->showSuccessModal = true;
            $this->showProcessingModal = false; // Ensure it's hidden on erro
            $this->showErrorModal = false;


            if (isset($response['data']) && !empty($response['data'])) {
                // data existe e não está vazia
                $customerId = $response['data']['customerId'];
                $redirectUrl = $response['data']['redirect_url'];
                $upsell_productId = $response['data']['upsell_productId'];
                if (!empty($redirectUrl)) {
                return redirect()->to($redirectUrl . "?customerId=" . $customerId . "&upsell_productId=" . $upsell_productId);
                } else {
                    return;
                }
            }
            $redirectUrl = $response['redirect_url'] ?? "https://web.snaphubb.online/obg/"; // Default or from response
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
            // Potentially show a generic error modal or message on the page
            $this->showErrorModal = true;
            $this->showProcessingModal = false; // Ensure it's hidden on erro
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

        // plano principal
        $cartItems[] = [
            'product_hash' => $currentPlanDetails['hash'],
            'title' => $this->product['title'] . ' - ' . $currentPlanDetails['label'],
            'price' => (int)round(floatval($currentPlanPriceInfo['descont_price']) * 100),
            'price_id' => $this->product['price_id'] ?? null,
            'recurring' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['recurring'] ?? null,
            'quantity' => 1,
            'operation_type' => 1,
        ];

        // bumps ativos
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

        // customer
        $customerData = [
            'name' => $this->cardName,
            'email' => $this->email,
            'phone_number' => preg_replace('/[^0-9+]/', '', $this->phone),
        ];
        if ($this->selectedCurrency === 'BRL' && $this->cpf) {
            $customerData['document'] = preg_replace('/\D/', '', $this->cpf);
        }

        $cardDetails = [
            'number' => $this->cardNumber,
            'holder_name' => $this->cardName,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvv' => $this->cardCvv,
        ];
        if ($this->selectedCurrency === 'BRL' && $this->cpf) {
            $cardDetails['document'] = preg_replace('/\D/', '', $this->cpf);
        }

        return [
            'amount' => (int)round($numeric_final_price * 100),
            'currency_code' => $this->selectedCurrency,
            'offer_hash' => $currentPlanDetails['hash'],
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
                'product_main_hash' => $this->product['hash'],
                'bumps_selected' => collect($this->bumps)->where('active', true)->pluck('id')->toArray(),
            ]
        ];
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
        $this->selectedPlan = 'quarterly'; // Assuming downsell is always to quarterly
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
        return []; // Removed Echo listeners
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
            // Recalculate plans and totals as language might affect labels (though prices should be language-agnostic)
            $this->plans = $this->getPlans(); // Re-fetch plans to update labels
            $this->calculateTotals();
            // Dispatch an event if JS needs to react to language change for UI elements not covered by Livewire re-render
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

    public function render()
    {
        return view('livewire.page-pay')->layoutData([
            'title' => __('payment.title'),
            'canonical' => url()->current(),
        ]);
    }
}