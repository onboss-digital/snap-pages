<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log; // Keep Log as it's used with full namespace

class PagePay extends Component
{

    public $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone,
        $plans, $modalData, $product;



    // Modais
    public $showSecure = false;
    public $showLodingModal = false;
    public $showDownsellModal = false;
    public $showUpsellModal = false;

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
        'title' => 'Acesso Exclusivo',
        'description' => 'Acesso a conteúdos ao vivo e eventos',
        'price' => 9.99,
        'hash' => 'xwe2w2p4ce_lxcb1z6opc',
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
        return [
            'cardName' => 'required|string|max:255',
            'cardNumber' => 'required|numeric|digits_between:13,19',
            'cardExpiry' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/?([0-9]{2})$/'],
            'cardCvv' => 'required|numeric|digits_between:3,4',
            'email' => 'required|email',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
        ];
    }

    public function mount()
    {
        $this->plans = $this->getPlans();

        // Recuperar preferências do usuário (antes em localStorage)
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();

        // Calcular valores iniciais
        $this->calculateTotals();

        // Iniciar contador de atividade
        $this->activityCount = rand(1, 50);

        $this->product = [
            'hash' => '3nidg2uzc0',
            'title' => 'Criptografía anónima',
            'cover' => 'https://d2lr0gp42cdhn1.cloudfront.net/3564404929/products/kox0kdggyhe4ggjgeilyhuqpd',
            'product_type' => 'digital',
            'guaranted_days' => 7,
            'sale_page' => 'https://snaphubb.com',
        ];
    }


    public function getPlans()
    {
        return [
            'monthly' => [
                'hash' => 'penev',
                'label' => __('payment.monthly'),
                'nunber_months' => 1,
                'prices' => [
                    'BRL' => [
                        'origin_price' => 94.90,
                        'descont_price' => 69.90,
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
                        'origin_price' => 242.00,
                        'descont_price' => 176.66,
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
            'annual' => [
                'hash' => 'cupxl',
                'label' => __('payment.annual'),
                'nunber_months' => 12,
                'prices' => [
                    'BRL' => [
                        'origin_price' => 783.49,
                        'descont_price' => 571.95,
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

        //


        $this->totals = [
            'month_price' => $prices['origin_price'] / $plan['nunber_months'],
            'month_price_discount' => $prices['descont_price'] / $plan['nunber_months'],
            'total_price' => $prices['origin_price'],
            'total_discount' => $prices['origin_price'] - $prices['descont_price'],
        ];


        $this->totals['final_price'] = $prices['descont_price'];

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
        // $this->cardExpiry is usually fine as MM/YY for regex (e.g., "12/25")

        $this->validate(); // Perform validation

        $this->showSecure = true;
        $this->showLodingModal = true;


        switch ($this->selectedPlan) {
            case 'monthly':
            case 'quarterly':
                $this->showUpsellModal = true;

                $offerValue = round($this->plans['annual']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['annual']['nunber_months'], 1);
                $offerDiscont = $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['origin_price'] * $this->plans['annual']['nunber_months'] -  $offerValue * $this->plans['annual']['nunber_months'];

                $this->modalData = [
                    'actual_month_value' => $this->totals['month_price_discount'],
                    'offer_month_value' => number_format($offerValue, 2, ',', '.'),
                    'offer_total_discount' => number_format($offerDiscont, 2, ',', '.'),
                    'offer_total_value' => number_format($this->plans['annual']['prices'][$this->selectedCurrency]['descont_price'], 2, ',', '.'),
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
        $this->selectedPlan = 'annual';
        $this->calculateTotals();
        $this->showUpsellModal = false;
        $this->sendCheckout();
    }



    public function sendCheckout()
    {
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

            // Log da resposta da API
            Log::channel('payment_checkout')->info('TriboPay API Response:', [
                'status' => $res->getStatusCode(),
                'body' => $responseBody,
                'timestamp' => now()
            ]);

            return redirect('http://web.snaphubb.online/ups-1');
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
        // Convert formatted string "1.234,50" or "69,90" to float 1234.50 or 69.90
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

        return [
            'amount' => $numeric_final_price * 100,
            'offer_hash' => $this->plans[$this->selectedPlan]['hash'],
            'payment_method' => 'credit_card',
            'card' => [
                'number' => $this->cardNumber,
                'holder_name' => $this->cardName,
                'exp_month' => $expMonth, // Use parsed month
                'exp_year' => $expYear,   // Use parsed year
                'cvv' => $this->cardCvv,
            ],
            'customer' => [
                'name' => $this->cardName,
                'email' => $this->email,
                'phone_number' => $this->phone,
            ],
            'cart' => [
                [
                    'product_hash' => $this->product['hash'],
                    'title' => $this->product['title'],
                    'price' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency]['descont_price'] * 100,
                    'quantity' => 1,
                    'operation_type' => 1
                ]
            ],
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
        return view('livewire.page-pay');
    }
}
