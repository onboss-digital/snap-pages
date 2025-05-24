<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Cache;

class PagePay extends Component
{

    public $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone,
        $plans, $modalData, $product;



    // Modais
    public $showSecure = false;
    public $showLodingModal = false;
    public $showDownsellModal = false;
    public $showUpsellModal = false;

    // public $showProcessingModal = false;
    // public $showPersonalizacaoModal = false;
    // public $showSegurancaVerificacao = false;

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

    // Cupom
    public $couponCode = '';
    public $couponApplied = false;
    public $couponDiscount = 0;
    public $couponMessage = '';
    public $couponMessageType = '';

    // Elementos de urgência
    public $spotsLeft = 12;
    public $activityCount = 0;

    // Modais

    public $showProcessingModal = false;
    public $showPersonalizacaoModal = false;
    public $showSegurancaVerificacao = false;

    // Valores calculados
    public $totals = [];
    public $listProducts = [];

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

        // dd($this->plans,  $this->selectedCurrency, $prices);


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
        $this->showSecure = true;

        // //validate form
        // $this->validate([
        //     'cardName' => 'required|string|max:255',
        //     'cardNumber' => 'required|string|max:255',
        //     'cardExpiry' => 'required|string|max:255',
        //     'cardCvv' => 'required|string|max:255',
        //     'email' => 'required|email|max:255',
        //     'phone' => 'required|string|max:255',
        // ]);


        // Store user data
        // $user = User::updateOrCreate(
        //     ['email' => $this->email],
        //     [
        //         'name' => $this->cardName,
        //         'phone' => $this->phone,
        //     ]
        // );
        // // Store user order
        // $order = Order::class::create([
        //     'user_id' => $user->id,
        //     'plan' => $this->selectedPlan,
        //     'currency' => $this->selectedCurrency,
        //     'price' => $this->totals['final_price'],
        // ]);

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
        dd($this->prepareCheckoutData());
        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // Construção do corpo da requisição baseado no estado atual
        // ...código existente para montar o corpo da requisição...

        try {
            $request = new Request(
                'POST',
                'https://api.tribopay.com.br/api/public/v1/transactions?api_token=lqyOgcoAfhxZkJ2bM606vGhmTur4I02USzs8l6N0JoH0ToN1zv31tZVDnTZU',
                $headers,
                json_encode($this->prepareCheckoutData())
            );

            $res = $client->sendAsync($request)->wait();

            // Log da resposta da API
            \Illuminate\Support\Facades\Log::info('TriboPay API Response', [
                'response' => $res->getBody()->getContents(),
                'timestamp' => now()
            ]);

            return redirect('http://web.snaphubb.online/ups-1');
        } catch (\Exception $e) {
            // Lidar com erros de API
            $this->addError('payment', 'Ocorreu um erro ao processar o pagamento: ' . $e->getMessage());
        }
    }


    private function prepareCheckoutData()
    { {
            return [
                'amount' => $this->totals['final_price'] * 100,
                'offer_hash' => $this->plans[$this->selectedPlan]['hash'],
                'payment_method' => 'credit_card',
                'card' => [
                    'number' => $this->cardNumber,
                    'holder_name' => $this->cardName,
                    'exp_month' => $this->cardExpiry,
                    'exp_year' => date('Y'),
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
                        'price' => $this->plans[$this->selectedPlan]['prices'][$this->selectedCurrency][''] * 100,
                        'quantity' => 1,
                        'operation_type' => 1
                    ]
                ],
                'installments' => 1,
            ];
        }
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
