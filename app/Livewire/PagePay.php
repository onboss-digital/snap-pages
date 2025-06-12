<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log; // Keep Log as it's used with full namespace

class PagePay extends Component
{

    public $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone, $cpf,
        $plans, $modalData, $product;



    // Modais
    public $showSecure = false;
    public $showLodingModal = false;
    public $showDownsellModal = false;
    public $showUpsellModal = false;

    public $showProcessingModal = false;

    public $selectedCurrency = 'BRL';
    public $selectedLanguage = 'br';
    // Order Bump
    public $selectedPlan = 'monthly';
    public $availableLanguages = [
        'br' => '🇧🇷 Português',
        'en' => '🇺🇸 English',
        'es' => '🇪🇸 Español',
    ];

    // Moedas e Conversão

    public $currencies = [
        'BRL' => [
            'symbol' => 'R$',
            'name' => 'Real Brasileiro',
            'code' => 'BRL',
            'label' => "payment.brl",
        ],
        'USD' => [
            'symbol' => '$',
            'name' => 'Dólar Americano',
            'code' => 'USD',
            'label' => "payment.usd",
        ],
        'EUR' => [
            'symbol' => '€',
            'name' => 'Euro',
            'code' => 'EUR',
            'label' => "payment.eur",
        ],
    ];
    // Planos e preços



    public $bumpActive = false;

    public $bump = [
        'id' => 4,
        'title' => 'Criptografía anónima',
        'description' => 'Acesso a conteúdos ao vivo e eventos',
        'price' => 9.99,
        'hash' => '3nidg2uzc0',
    ];

    // Contador regressivo
    public $countdownMinutes = 14;
    public $countdownSeconds = 22;

    // Elementos de urgência
    public $spotsLeft = 12;
    public $activityCount = 0;

    // Modais duplicadas removidas

    // Valores calculados
    public $totals = [];
    // public $listProducts = []; // Removed as it's unused and never populated

    // Dados de benefícios
    public $benefits = [
        [
            'title' => 'Vídeos premium',
            'description' => 'Acesso a todo nosso conteúdo sem restrições'
        ],
        [
            'title' => 'Conteúdos diários',
            'description' => 'Novas atualizações todos os dias'
        ],
        [
            'title' => 'Sem anúncios',
            'description' => 'Experiência limpa e sem interrupções'
        ],
        [
            'title' => 'Personalização',
            'description' => 'Configure sua conta como preferir'
        ],
        [
            'title' => 'Atualizações semanais',
            'description' => 'Novas funcionalidades toda semana'
        ],
        [
            'title' => 'Votação e sugestões',
            'description' => 'Ajude a moldar o futuro da plataforma'
        ]
    ];

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

        // CPF is required only when currency is BRL (Brazilian Real)
        if ($this->selectedCurrency === 'BRL') {
            $rules['cpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
        }

        return $rules;
    }

    public function mount()
    {
        $this->plans = $this->getPlans();

        // $bal = "{\r\n    \"amount\": 500,\r\n    \"offer_hash\": \"s7b5e\", // hash de uma oferta\r\n    \"payment_method\": \"pix\", // credit_card, billet, pix\r\n    \"card\": {\r\n        \"number\": \"4111 1111 1111 1111\",\r\n        \"holder_name\": \"Teste Holder name\",\r\n        \"exp_month\": 12,\r\n        \"exp_year\": 2025,\r\n        \"cvv\": \"123\"\r\n    },\r\n    \"customer\": {\r\n        \"name\": \"Customer name\",\r\n        \"email\": \"email@email.com\",\r\n        \"phone_number\": \"21975784612\",\r\n        \"document\": \"09115751031\",\r\n        \"street_name\": \"Nome da Rua\",\r\n        \"number\": \"sn\",\r\n        \"complement\": \"Lt19 Qd 134\",\r\n        \"neighborhood\": \"Centro\",\r\n        \"city\": \"Itaguaí\",\r\n        \"state\": \"RJ\",\r\n        \"zip_code\": \"23822180\"\r\n    },\r\n    \"cart\": [\r\n        {\r\n            \"product_hash\": \"so4neitign\",\r\n            \"title\": \"Produto Teste API Publica\",\r\n            \"cover\": null,\r\n            \"price\": 10000,\r\n            \"quantity\": 2,\r\n            \"operation_type\": 1,\r\n            \"tangible\": false\r\n        }\r\n    ],\r\n    \"installments\": 1,\r\n    \"expire_in_days\": 1,\r\n    \"postback_url\": \"\", // URL PARA RECEBER ATUALIZAÇÃO DAS TRANSAÇÕES\r\n    \"tracking\": {\r\n        \"src\": \"\",\r\n        \"utm_source\": \"\",\r\n        \"utm_medium\": \"\",\r\n        \"utm_campaign\": \"\",\r\n        \"utm_term\": \"\",\r\n        \"utm_content\": \"\"\r\n    }\r\n}";
        // // Remove \n e \r do JSON
        // $bal = str_replace(["\n", "\r"], '', $bal);
        // dd($bal);

        // Debug
        // $this->cardName = 'John Doe';
        // $this->cardNumber = '4111111111111111';
        // $this->cardExpiry = '12/25';
        // $this->cardCvv = '123';
        // $this->email = 'john@mail.com';
        // $this->phone = '+5511999999999';

        // Recuperar preferências do usuário (antes em localStorage)
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();

        // Calcular valores iniciais
        $this->calculateTotals();

        // Iniciar contador de atividade
        $this->activityCount = rand(1, 50);

        $this->product = [
            'hash' => '8v1zcpkn9j',
            'title' => 'SNAPHUBB BR',
            'cover' => 'https://d2lr0gp42cdhn1.cloudfront.net/3564404929/products/ua11qf25qootxsznxicnfdbrd',
            'product_type' => 'digital',
            'guaranted_days' => 7,
            'sale_page' => 'https://snaphubb.online',
        ];
    }


    public function getPlans()
    {
        return [
            'monthly' => [
                'hash' => 'rwquocfj5c',
                'label' => __('payment.monthly'),
                'nunber_months' => 1,
                'prices' => [
                    'BRL' => [
                        'origin_price' => 39.90,
                        'descont_price' => 29.90,
                        'currency' => 'BRL',
                    ],
                    'USD' => [
                        'origin_price' => 17.08,
                        'descont_price' => 12.47,
                        'currency' => 'USD',
                    ],
                    'EUR' => [
                        'origin_price' => 15.18,
                        'descont_price' => 11.08,
                        'currency' => 'EUR',
                    ],
                    'ARS' => [
                        'origin_price' => 19067.31,
                        'descont_price' => 13919.76,
                        'currency' => 'ARS',
                    ],
                ],
            ],
            'quarterly' => [
                'hash' => 'velit nostrud dolor in deserunt',
                'label' => __('payment.quarterly'),
                'nunber_months' => 3,
                'prices' => [
                    'BRL' => [
                        'origin_price' => 189.70,
                        'descont_price' => 69.90,
                        'currency' => 'BRL',
                    ],
                    'USD' => [
                        'origin_price' => 43.56,
                        'descont_price' => 31.80,
                        'currency' => 'USD',
                    ],
                    'EUR' => [
                        'origin_price' => 38.72,
                        'descont_price' => 28.27,
                        'currency' => 'EUR',
                    ],
                    'ARS' => [
                        'origin_price' => 48622.64,
                        'descont_price' => 35494.69,
                        'currency' => 'ARS',
                    ],
                ],
            ],
            'semi-annual' => [
                'hash' => 'cupxl',
                'label' => __(key: 'payment.semi-annual'),
                'nunber_months' => 6,
                'prices' => [
                    'BRL' => [
                        'origin_price' => 309.60,
                        'descont_price' => 89.90,
                        'currency' => 'BRL',
                    ],
                    'USD' => [
                        'origin_price' => 141.03,
                        'descont_price' => 102.95,
                        'currency' => 'USD',
                    ],
                    'EUR' => [
                        'origin_price' => 125.36,
                        'descont_price' => 91.51,
                        'currency' => 'EUR',
                    ],
                    'ARS' => [
                        'origin_price' => 157412.81,
                        'descont_price' => 114911.48,
                        'currency' => 'ARS',
                    ],
                ],
            ]
        ];
    }
    public function calculateTotals()
    {

        $plan = $this->plans[$this->selectedPlan];
        $prices = $plan['prices'][$this->selectedCurrency];

        $this->totals = [
            'month_price' => $prices['origin_price'] / $plan['nunber_months'],
            'month_price_discount' => $prices['descont_price'] / $plan['nunber_months'],
            'total_price' => $prices['origin_price'],
            'total_discount' => $prices['origin_price'] - $prices['descont_price'],
        ];

        // Add bump price if bumpActive is true
        $finalPrice = $prices['descont_price'];
        if ($this->bumpActive && isset($this->bump['price'])) {
            $finalPrice += $this->bump['price'];
        }
        $this->totals['final_price'] = $finalPrice;

        $this->totals = array_map(function ($value) {
            return number_format(round($value, 1), 2, ',', '.');
        }, $this->totals);
    }

    public function startCheckout()
    {
        // Clean inputs before validation
        if ($this->cardNumber) {
            $this->cardNumber = preg_replace('/\D/', '', $this->cardNumber);
        }
        if ($this->cardCvv) {
            $this->cardCvv = preg_replace('/\D/', '', $this->cardCvv);
        }
        if ($this->phone) {
            $this->phone = preg_replace('/[^0-9+]/', '', $this->phone); // Keep + for international
        }
        // Format CPF if provided (for BRL currency)
        if ($this->cpf && $this->selectedCurrency === 'BRL') {
            // First remove all non-numeric characters
            $cpf = preg_replace('/\D/', '', $this->cpf);
            // Then format if it has 11 digits
            if (strlen($cpf) == 11) {
                $this->cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
            }
        }
        // $this->cardExpiry is usually fine as MM/YY for regex (e.g., "12/25")

        $this->validate(); // Perform validation

        $this->showSecure = true;
        $this->showLodingModal = true;


        switch ($this->selectedPlan) {
            case 'monthly':
            case 'quarterly':
                $this->showUpsellModal = true;

                $offerValue = round($this->plans['semi-annual']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['semi-annual']['nunber_months'], 1);
                $offerDiscont = $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['origin_price'] * $this->plans['semi-annual']['nunber_months'] -  $offerValue * $this->plans['semi-annual']['nunber_months'];

                $this->modalData = [
                    'actual_month_value' => $this->totals['month_price_discount'],
                    'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                    'offer_total_discount' => number_format($offerDiscont, 2, ',', '.'),
                    'offer_total_value' => number_format($this->plans['semi-annual']['prices'][$this->selectedCurrency]['descont_price'], 2, ',', '.'),
                ];

                break;
            default:
                return $this->sendCheckout();
        }

        $this->showLodingModal = false;
    }

    public function rejectUpsell()
    {
        $this->showUpsellModal = false;
        $offerValue = round($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['quarterly']['nunber_months'], 1);
        $offerDiscont = $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['origin_price'] * $this->plans['quarterly']['nunber_months'] -  $offerValue * $this->plans['quarterly']['nunber_months'];

        $this->modalData = [
            'actual_month_value' => $this->totals['month_price_discount'],
            'offer_month_value' => number_format($offerValue, 2, ',', '.'),
            'offer_total_discount' => number_format($offerDiscont, 2, ',', '.'),
            'offer_total_value' => number_format($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'], 2, ',', '.'),
        ];

        if ($this->selectedPlan === 'quarterly') {
            $this->sendCheckout();
        }

        $this->showDownsellModal = true;
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
        $this->showDownsellModal = $this->showUpsellModal = false;
        $this->showProcessingModal = true;
        //
        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // Construção do corpo da requisição baseado no estado atual
        // ...código existente para montar o corpo da requisição...



        $checkoutData = $this->prepareCheckoutData(); // Define $checkoutData before try block

        // Create a deep copy for logging and mask sensitive data
        $loggedData = $checkoutData;
        if (isset($loggedData['card']['number'])) {
            $loggedData['card']['number'] = '**** **** **** ' . substr($checkoutData['card']['number'], -4);
        }
        if (isset($loggedData['card']['cvv'])) {
            $loggedData['card']['cvv'] = '***';
        }
        // Debuggin g point to inspect $checkoutData
        try {

            Log::channel('payment_checkout')->info('Preparing TriboPay Checkout. Data:', $loggedData);

            $request = new Request(
                'POST',
                'https://api.tribopay.com.br/api/public/v1/transactions?api_token=lqyOgcoAfhxZkJ2bM606vGhmTur4I02USzs8l6N0JoH0ToN1zv31tZVDnTZU',
                $headers,
                json_encode($checkoutData) // Use the variable that was logged
            );

            $res = $client->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents(); // Read body once
            // Debugging point
            $dataResponse = json_decode($responseBody, true);


            // Log da resposta da API
            Log::channel('payment_checkout')->info('TriboPay API Response:', [
                'status' => $res->getStatusCode(),
                'body' => $responseBody,
                'timestamp' => now()
            ]);
            $domain = urlencode(env('APP_URL'));

            return redirect("https://web.snaphubb.online/obg/");
        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('TriboPay API Error:', [
                'message' => $e->getMessage(),
                'request_data' => $loggedData, // Log masked data
                // 'trace' => $e->getTraceAsString(), // Optional: trace can be very verbose
            ]);
            // Lidar com erros de API
            $this->addError('payment', 'Ocorreu um erro ao processar o pagamento: ' . $e->getMessage());
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

        $cart[] = [
            'product_hash' => $this->product['hash'],
            'title' => $this->product['title'],
            'price' => (int)($this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['descont_price'] * 100), // Convert to cents
            'quantity' => 1,
            'operation_type' => 1, // Assuming 1 is the correct operation type
        ];

        if($this->bumpActive) {
            $cart[] = [
                'product_hash' => $this->bump['hash'],
                'title' => $this->bump['title'],
                'price' => (int)($this->bump['price'] * 100), // Convert to cents
                'quantity' => 1,
                'operation_type' => 2, // Assuming 1 is the correct operation type
            ];

        }

        return [
            'amount' => (int)($numeric_final_price * 100), // Convert to cents
            'offer_hash' => $this->plans[$this->selectedPlan]['hash'],
            'payment_method' => 'credit_card',
            'card' => [
                'number' => $this->cardNumber,
                'holder_name' => $this->cardName,
                'exp_month' => $expMonth, // Use parsed month
                'exp_year' => $expYear,   // Use parsed year
                'cvv' => $this->cardCvv,
                'cpf' => $this->selectedCurrency === 'BRL' ? preg_replace('/[^0-9]/', '', $this->cpf) : null,
            ],
            'customer' => [
                'name' => $this->cardName,
                'email' => $this->email,
                'phone_number' => $this->phone,
                'document' => $this->selectedCurrency === 'BRL' ? preg_replace('/[^0-9]/', '', $this->cpf) : null,
            ],
            'cart' => $cart,
            'installments' => 1,
        ];
    }

    public function decrementTimer()
    {
        if ($this->countdownSeconds > 0) {
            $this->countdownSeconds--;
        } elseif ($this->countdownMinutes > 0) {
            $this->countdownSeconds = 59;
            $this->countdownMinutes--;
        } else {
            // Timer has reached 00:00, do nothing or dispatch an event
            // For example: $this->dispatch('timerEnded');
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

    // Livewire Polling para simulação de atividade
    public function getListeners()
    {
        return [
            'echo:activity,ActivityEvent' => 'updateActivityCount',
        ];
    }

    public function updateActivityCount()
    {
        $this->activityCount = rand(1, 50);
    }

    public function changeLanguage($lang)
    {
        session(['locale' => $lang]);
        app()->setLocale($lang);
        $this->selectedLanguage = $lang;
        $this->calculateTotals();
    }

    public function decrementSpotsLeft()
    {
        if (rand(1, 5) == 1) { // 20% chance
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
