<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;

class PagePay extends Component
{
    // Variáveis de estado principais (antes em JavaScript)
    public $availableLanguages = [
        'br' => '🇧🇷 Português',
        'en' => '🇺🇸 English',
        'es' => '🇪🇸 Español',
    ];

    // Moedas e Conversão
    public $selectedCurrency = 'BRL';
    public $currencySymbols = [
        'BRL' => 'R$',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    public $conversionRates = [
        'BRL' => 1.0,
        'USD' => 0.17,
        'EUR' => 0.16,
        'GBP' => 0.13
    ];

    // Planos e preços
    public $plans;
    public $selectedPlan = 'monthly';
    public $selectedLanguage = 'br';
    public $basePrices = [
        'monthly' => 58.99,
        'quarterly' => 53.09, // 10% off
        'annual' => 44.24,    // 25% off
        'bump' => 9.99
    ];

    // Order Bump
    public $bumpActive = false;
    public $bump = [
        'id' => 4,
        'title' => 'Acesso Exclusivo',
        'description' => 'Acesso a conteúdos ao vivo e eventos',
        'price' => 9.99,
        'hash' => 'xwe2w2p4ce_lxcb1z6opc',
    ];

    // Checkout progressivo
    public $progressStep = 1;

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
    public $showUpsellModal = false;
    public $showDownsellModal = false;
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

    // Lifecycle hooks
    public function mount()
    {
        // Inicializar planos com mesma estrutura do arquivo original
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

        // Recuperar preferências do usuário (antes em localStorage)
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();

        // Detectar localização e moeda (antes em JavaScript)
        $this->detectCurrencyByGeolocation();

        // Calcular valores iniciais
        $this->calculateTotals();

        // Iniciar contador de atividade
        $this->activityCount = rand(1, 50);
    }

    // Métodos para atualização reativa

    public function updateCurrency($currency)
    {
        $this->selectedCurrency = $currency;
        Session::put('selectedCurrency', $currency);
        $this->calculateTotals();
        $this->showPersonalizacao();
    }

    public function updatePlan($plan)
    {
        $this->selectedPlan = $plan;
        Session::put('selectedPlan', $plan);
        $this->calculateTotals();
        $this->updateProgress(max($this->progressStep, 2));
        $this->showPersonalizacao();
    }

    public function toggleBump()
    {
        $this->bumpActive = !$this->bumpActive;
        if ($this->bumpActive) {
            $this->updateProgress(max($this->progressStep, 3));
            $this->spotsLeft--;
        }
        $this->calculateTotals();
    }

    public function applyCoupon()
    {
        $couponCode = strtoupper($this->couponCode);

        if ($couponCode === 'DESCONTO20') {
            $this->couponApplied = true;
            $this->couponDiscount = 0.20; // 20% discount
            $this->couponMessage = 'Cupom de 20% aplicado com sucesso!';
            $this->couponMessageType = 'success';
            $this->updateProgress(max($this->progressStep, 3));
        } elseif ($couponCode === 'PROMO10') {
            $this->couponApplied = true;
            $this->couponDiscount = 0.10; // 10% discount
            $this->couponMessage = 'Cupom de 10% aplicado com sucesso!';
            $this->couponMessageType = 'success';
            $this->updateProgress(max($this->progressStep, 3));
        } else {
            $this->couponMessage = 'Cupom inválido, tente novamente.';
            $this->couponMessageType = 'error';
        }

        $this->calculateTotals();
    }

    // Métodos auxiliares

    public function convertPrice($priceInBRL, $currency)
    {
        return $priceInBRL * $this->conversionRates[$currency];
    }

    public function formatPrice($price, $currency)
    {
        return $this->currencySymbols[$currency] . number_format($price, 2, ',', '.');
    }

    public function calculateTotals()
    {
        $this->updateListProducts();

        $originalPrice = 0.0;
        foreach ($this->listProducts as $product) {
            $originalPrice += floatval(str_replace([',', 'R$', '$', '€', '£'], ['.', '', '', '', ''], $product['price']));
        }

        // Aplicar desconto do cupom
        $discount = $this->couponApplied ? $originalPrice * $this->couponDiscount : 0.0;
        $totalPay = $originalPrice - $discount;

        $planPrice = $this->convertPrice($this->basePrices[$this->selectedPlan], $this->selectedCurrency);

        $this->totals = [
            'original_price' => $this->formatPrice($originalPrice, $this->selectedCurrency),
            'discount' => $this->formatPrice($discount, $this->selectedCurrency),
            'total_pay' => $this->formatPrice($totalPay, $this->selectedCurrency),
            'real_price' => $this->formatPrice(89.90, $this->selectedCurrency), // Preço "original" antes do desconto
            'descont_price' => $this->formatPrice($planPrice, $this->selectedCurrency),
            'total_price' => $this->formatPrice($totalPay, $this->selectedCurrency),
        ];
    }

    public function updateListProducts()
    {
        $plan = $this->plans[$this->selectedLanguage][$this->selectedPlan] ?? null;
        $this->listProducts = [];
        if ($plan) {
            $planPrice = $this->convertPrice($this->basePrices[$this->selectedPlan], $this->selectedCurrency);
            $this->listProducts[] = [
                'name' => __('payment.premium_subscription'),
                'price' => $this->formatPrice($planPrice, $this->selectedCurrency),
            ];
        }

        if ($this->bumpActive) {
            $bumpPrice = $this->convertPrice($this->bump['price'], $this->selectedCurrency);
            $this->listProducts[] = [
                'name' => $this->bump['title'],
                'price' => $this->formatPrice($bumpPrice, $this->selectedCurrency),
            ];
        }
    }

    // Manipulação de modais e estados visuais

    public function updateProgress($step)
    {
        $this->progressStep = $step;
    }

    public function startCheckout()
    {
        $this->updateProgress(4);
        $this->showSeguranca();
    }

    public function showSeguranca()
    {
        $this->showSegurancaVerificacao = true;
        $this->dispatch('hideSeguranca')
            ->later(3000);
    }

    public function hideSeguranca()
    {
        $this->showSegurancaVerificacao = false;
        $this->showProcessing();
    }

    public function showProcessing()
    {
        $this->showProcessingModal = true;
        $this->dispatch('hideProcessing')
            ->later(2000);
    }

    public function hideProcessing()
    {
        $this->showProcessingModal = false;
        if ($this->selectedPlan === 'monthly') {
            $this->showUpsell();
        } else {
            $this->sendCheckout();
        }
    }

    public function showUpsell()
    {
        $this->showUpsellModal = true;
    }

    public function acceptUpsell()
    {
        $this->selectedPlan = 'annual';
        $this->calculateTotals();
        $this->showUpsellModal = false;
        $this->sendCheckout();
    }

    public function rejectUpsell()
    {
        $this->showUpsellModal = false;
        $this->showDownsell();
    }

    public function showDownsell()
    {
        $this->showDownsellModal = true;
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

    public function showPersonalizacao()
    {
        $this->showPersonalizacaoModal = true;
        $this->dispatch('hidePersonalizacao')
            ->later(3000);
    }

    public function hidePersonalizacao()
    {
        $this->showPersonalizacaoModal = false;
    }

    // Métodos de geolocalização e integração externa

    public function detectCurrencyByGeolocation()
    {
        try {
            $client = new Client();
            $response = $client->request('GET', 'https://ipapi.co/json');
            $data = json_decode($response->getBody(), true);

            if (isset($data['currency']) && in_array($data['currency'], ['USD', 'EUR', 'GBP'])) {
                $this->selectedCurrency = $data['currency'];
                Session::put('selectedCurrency', $this->selectedCurrency);
            }
        } catch (\Exception $e) {
            // Silenciar erros de geolocalização
        }
    }

    // Processamento final do checkout

    public function sendCheckout()
    {
        dd('sendCheckout');
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
    {
        // Construir dados para a API baseados no estado atual
        // ...
    }

    // Livewire Polling para simulação de atividade
    public function getListeners()
    {
        return [
            'echo:activity,ActivityEvent' => 'updateActivityCount',
            'hideSeguranca' => 'hideSeguranca',
            'hideProcessing' => 'hideProcessing',
            'hidePersonalizacao' => 'hidePersonalizacao',
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

    public function render()
    {
        return view('livewire.page-pay');
    }
}
