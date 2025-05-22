<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;

class PagePay extends Component
{
    public $availableLanguages = [
        'br' => '🇧🇷 Português',
        'en' => '🇺🇸 English',
        'es' => '🇪🇸 Español',
    ];


    public $plans;

    // Novas variáveis para controle do checkout
    public $selectedPlan = 'monthly';
    public $selectedLanguage = 'br';
    public $selectedCurrency = 'brl';
    public $bumpActive = false;
    public $coupon = '';
    public $discount = 0.0;

    public  $bump = [
        'id' => 4,
        'title' => 'Acesso Exclusivo',
        'description' => 'Acesso a conteúdos ao vivo e eventos',
        'price' => 9.99,
        'hash' => 'xwe2w2p4ce_lxcb1z6opc',
    ];

    public $totals = [];

    public $listProducts;


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
        $this->plans = [
            'br' => [
                'monthly' => [
                    'hash' => 'penev',
                    'label' => __('payment.monthly'),
                    'price' => 60.00,
                ],
                'quarterly' => [
                    'hash' => 'velit nostrud dolor in deserunt',
                    'label' => __('payment.quarterly'),
                    'price' => 265.00,
                ],
                'annual' => [
                    'hash' => 'cupxl',
                    'label' => __('payment.annual'),
                    'price' => 783.00,
                ]
            ],
            'en' => [
                'monthly' => [
                    'hash' => 'penev',
                    'label' => __('payment.monthly'),
                    'price' => 60.00,
                ],
                'quarterly' => [
                    'hash' => 'velit nostrud dolor in deserunt',
                    'label' => __('payment.quarterly'),
                    'price' => 265.00,
                ],
                'annual' => [
                    'hash' => 'cupxl',
                    'label' => __('payment.annual'),
                    'price' => 783.00,
                ]
            ],
            'es' => [
                'monthly' => [
                    'hash' => 'penev',
                    'label' => __('payment.monthly'),
                    'price' => 60.00,
                ],
                'quarterly' => [
                    'hash' => 'velit nostrud dolor in deserunt',
                    'label' => __('payment.quarterly'),
                    'price' => 265.00,
                ],
                'annual' => [
                    'hash' => 'cupxl',
                    'label' => __('payment.annual'),
                    'price' => 783.00,
                ]
            ]
        ];
        // Inicializa lista de produtos baseada no plano selecionado
        $this->calculateTotals();
    }

    /**
     * Atualiza a lista de produtos conforme o plano e bump selecionados
     */
    public function updateListProducts()
    {
        $plan = $this->plans[$this->selectedLanguage][$this->selectedPlan] ?? null;
        $this->listProducts = [];
        if ($plan) {
            $this->listProducts[] = [
                'name' => __('payment.premium_subscription'),
                'price' => $plan['price'],
            ];
        }
        if ($this->bumpActive) {
            $this->listProducts[] = [
                'name' => $this->bump['title'],
                'price' => $this->bump['price'],
            ];
        }
    }


    public function sendCheckout()
    {
        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        // Upsell
        // $body = '{
        //             "amount": 783,
        //             "offer_hash": "xwe2w2p4ce",
        //             "payment_method": "credit_card",
        //             "card": {
        //                 "number": "5162923872236696",
        //                 "holder_name": "Anderson N Isotton",
        //                 "exp_month": 05,
        //                 "exp_year": 2033,
        //                 "cvv": "862"
        //             },
        //             "customer": {
        //                 "name": "Pulga Louca",
        //                 "email": "pulga@yahoo.com",
        //                 "phone_number": "(983) 955-1031"
        //             },
        //             "cart": [
        //                 {
        //                     "product_hash": "annual-USD",
        //                     "title": "Product test",
        //                     "price": 783,
        //                     "quantity": 1,
        //                     "operation_type": 1
        //                 }
        //             ],
        //             "installments": 1,
        //             "expire_in_days": 15
                    // }';
        $body = '{
                    "amount": 9700,
                    "offer_hash": "penev",
                    "payment_method": "credit_card",
                    "card": {
                        "number": "2",
                        "holder_name": "Betty Morissette",
                        "exp_month": 47372423,
                        "exp_year": 2025,
                        "cvv": "277"
                    },
                    "customer": {
                        "name": "Pulga Louca",
                        "email": "pulga@yahoo.com",
                        "phone_number": "(983) 955-1031"
                    },
                    "cart": [
                        {
                            "product_hash": "xwe2w2p4ce",
                            "title": "Product test",
                            "price": 9700,
                            "quantity": 1,
                            "operation_type": 1
                        }
                    ],
                    "installments": 1,
                    "expire_in_days": 15
                    }';
        $request = new Request('POST', 'https://api.tribopay.com.br/api/public/v1/transactions?api_token=lqyOgcoAfhxZkJ2bM606vGhmTur4I02USzs8l6N0JoH0ToN1zv31tZVDnTZU', $headers, $body);
        $res = $client->sendAsync($request)->wait();


        // Log the API response
        \Illuminate\Support\Facades\Log::info('TriboPay API Response', [
            'response' => $res->getBody()->getContents(),
            'timestamp' => now()
        ]);


        return redirect('http://web.snaphubb.online/ups-1');
    }

    public function render()
    {
        return view('livewire.page-pay');
    }

    public function changeLanguage($lang)
    {
        session(['locale' => $lang]);
        app()->setLocale($lang);
    }

    /**
     * Calcula totais do pedido (preço original, desconto, total a pagar)
     */
    public function calculateTotals()
    {
        $this->updateListProducts();

        $originalPrice = 0.0;
        foreach ($this->listProducts as $product) {
            $originalPrice += floatval(str_replace([',', 'R$', '$'], ['.', '', ''], $product['price']));
        }

        // Exemplo de desconto: 10% se cupom "PROMO10" for aplicado
        $discount = 0.0;
        if (strtoupper($this->coupon) === 'PROMO10') {
            $discount = $originalPrice * 0.10;
        }

        $totalPay = $originalPrice - $discount;

        $this->totals = [
            'original_price' => number_format($originalPrice, 2, ',', '.'),
            'discount' => number_format($discount, 2, ',', '.'),
            'total_pay' => number_format($totalPay, 2, ',', '.'),
            'real_price' => number_format($originalPrice, 2, ',', '.'),
            'descont_price' => number_format($totalPay, 2, ',', '.'),
            'total_price' => number_format($totalPay, 2, ',', '.'),
        ];
    }
}
