<?php

namespace App\Livewire;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Cache;

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
    // Store rates against USD, e.g., ['USD' => 1.0, 'BRL' => 5.05, 'EUR' => 0.92]
    public $conversionRates = []; // Will be populated from cache/API
    public $exchangeRateBase = 'USD'; // Base currency for fetched rates

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
        $this->updateAndCacheConversionRates(); // Load rates first

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
        // This might set selectedCurrency. calculateTotals needs to be called after this.
        $this->detectCurrencyByGeolocation(); 

        // Calcular valores iniciais
        $this->calculateTotals();

        // Iniciar contador de atividade
        $this->activityCount = rand(1, 50);
    }

    private function fetchConversionRates($base = 'USD')
    {
        // In a real app, use an API key from a service like exchangerate-api.com
        // $apiKey = config('services.exchangerateapi.key');
        // $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}";
        
        // Simulate API response structure:
        $mockApiResponse = [
            'result' => 'success',
            'base_code' => $base,
            'conversion_rates' => [
                'USD' => 1.0,
                'BRL' => 5.05, // Example: 1 USD = 5.05 BRL
                'EUR' => 0.92, // Example: 1 USD = 0.92 EUR
                'GBP' => 0.79, // Example: 1 USD = 0.79 GBP
                // Add other currencies as needed by $this->currencySymbols
            ]
        ];

        if ($mockApiResponse['result'] === 'success' && isset($mockApiResponse['conversion_rates'])) {
            return $mockApiResponse['conversion_rates'];
        }
        return null; // Or throw an exception
    }

    public function updateAndCacheConversionRates()
    {
        $this->conversionRates = Cache::remember('conversion_rates_' . $this->exchangeRateBase, now()->addHours(6), function () {
            return $this->fetchConversionRates($this->exchangeRateBase);
        });

        // If fetch/cache fails, use fallback
        if (empty($this->conversionRates)) {
            // This fallback should ideally match the structure of fetched rates (USD based)
            $this->conversionRates = [
                'USD' => 1.0, 
                'BRL' => 5.0,  // 1 USD = 5 BRL
                'EUR' => 0.9,  // 1 USD = 0.9 EUR
                'GBP' => 0.8   // 1 USD = 0.8 GBP
            ]; 
        }
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
        // $this->bumpActive is already updated by wire:model="bumpActive" from the checkbox
        if ($this->bumpActive) {
            $this->updateProgress(max($this->progressStep, 3));
            if ($this->spotsLeft > 3) { // Ensure spotsLeft doesn't go below a reasonable minimum
                $this->spotsLeft--;
            }
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

    public function convertPrice($priceInBRL, $targetCurrency)
    {
        // Ensure rates are loaded, especially if mount hasn't run or cache failed.
        if (empty($this->conversionRates) || !isset($this->conversionRates[$this->exchangeRateBase])) {
            $this->updateAndCacheConversionRates(); // Try to load them again
        }
        
        // After attempting to load, check again. If still empty, use a very basic fallback.
        if (empty($this->conversionRates) || !isset($this->conversionRates[$this->exchangeRateBase])) {
            if ($targetCurrency === 'BRL') return $priceInBRL;
            // Extremely rough BRL to X, only if API/Cache totally failed
            $fallbackRates = ['USD' => 0.20, 'EUR' => 0.18, 'GBP' => 0.15, 'BRL' => 1.0];
            return $priceInBRL * ($fallbackRates[$targetCurrency] ?? 0.20); // Default to USD if target not in rough map
        }

        // Case 1: Target currency is the same as the API base currency (USD)
        if ($targetCurrency === $this->exchangeRateBase) {
            if (!isset($this->conversionRates['BRL']) || $this->conversionRates['BRL'] == 0) {
                return $priceInBRL; // Should not happen with proper rates
            }
            return $priceInBRL / $this->conversionRates['BRL']; // Convert BRL to USD
        }

        // Case 2: Target currency is BRL (base price currency)
        // Since rates are USD based, this means converting BRL -> USD -> BRL.
        // This path is mostly for consistency in the conversion logic.
        if ($targetCurrency === 'BRL') {
             if (!isset($this->conversionRates['BRL']) || $this->conversionRates['BRL'] == 0) {
                return $priceInBRL; // Avoid division by zero or if BRL rate missing
            }
            // Convert BRL to USD, then USD back to BRL. Result should be $priceInBRL.
            // $priceInUSD = $priceInBRL / $this->conversionRates['BRL'];
            // $priceInTargetBRL = $priceInUSD * $this->conversionRates['BRL'];
            // return $priceInTargetBRL;
            return $priceInBRL; // Direct return as no effective conversion is needed.
        }
        
        // Case 3: General conversion (e.g., BRL to EUR)
        // Requires BRL rate and target currency rate against the API base (USD)
        if (!isset($this->conversionRates['BRL']) || !isset($this->conversionRates[$targetCurrency]) || $this->conversionRates['BRL'] == 0) {
             // If BRL rate or target rate against USD is missing, or BRL rate is zero, cannot convert.
             // Fallback: return original BRL price or a very rough estimate if not BRL.
             return $targetCurrency === 'BRL' ? $priceInBRL : $priceInBRL * 0.2; // Default to a rough USD estimate
        }

        // 1. Convert original BRL price to the API's base currency (USD)
        $priceInApiBase = $priceInBRL / $this->conversionRates['BRL']; // e.g. 58.99 BRL / 5.05 (BRL_PER_USD) = 11.68 USD
        
        // 2. Convert from API base currency (USD) to the target currency
        $finalPrice = $priceInApiBase * $this->conversionRates[$targetCurrency]; // e.g. 11.68 USD * 0.92 (EUR_PER_USD) = 10.75 EUR
        
        return $finalPrice;
    }

    public function formatPrice($price, $currency)
    {
        return $this->currencySymbols[$currency] . number_format($price, 2, ',', '.');
    }

    public function calculateTotals()
    {
        $this->updateListProducts(); // Ensure listProducts is up-to-date with raw prices

        $rawOriginalPrice = 0.0;
        foreach ($this->listProducts as $product) {
            $rawOriginalPrice += $product['raw_price'];
        }

        // Aplicar desconto do cupom
        $rawCouponDiscount = $this->couponApplied ? $rawOriginalPrice * $this->couponDiscount : 0.0;
        $rawTotalPay = $rawOriginalPrice - $rawCouponDiscount;

        // Price for the selected plan unit (e.g., monthly price, quarterly price)
        $rawSelectedPlanUnitPrice = $this->convertPrice($this->basePrices[$this->selectedPlan], $this->selectedCurrency);
        
        // Fixed "compare at" price of 89.90 BRL, converted to the selected currency.
        $rawRealPrice = $this->convertPrice(89.90, $this->selectedCurrency);


        $this->totals = [
            'original_price' => $this->formatPrice($rawOriginalPrice, $this->selectedCurrency),
            'discount' => $this->formatPrice($rawCouponDiscount, $this->selectedCurrency),
            'total_pay' => $this->formatPrice($rawTotalPay, $this->selectedCurrency),
            'real_price' => $this->formatPrice($rawRealPrice, $this->selectedCurrency), 
            'descont_price' => $this->formatPrice($rawSelectedPlanUnitPrice, $this->selectedCurrency),
            'total_price' => $this->formatPrice($rawTotalPay, $this->selectedCurrency), // Often same as total_pay
        ];
    }

    public function updateListProducts()
    {
        $plan = $this->plans[$this->selectedLanguage][$this->selectedPlan] ?? null;
        $this->listProducts = [];
        if ($plan) {
            $rawPlanPrice = $this->convertPrice($this->basePrices[$this->selectedPlan], $this->selectedCurrency);
            $this->listProducts[] = [
                'name' => __('payment.premium_subscription'),
                'formatted_price' => $this->formatPrice($rawPlanPrice, $this->selectedCurrency),
                'raw_price' => $rawPlanPrice,
            ];
        }

        if ($this->bumpActive) {
            $rawBumpPrice = $this->convertPrice($this->bump['price'], $this->selectedCurrency);
            $this->listProducts[] = [
                'name' => $this->bump['title'],
                'formatted_price' => $this->formatPrice($rawBumpPrice, $this->selectedCurrency),
                'raw_price' => $rawBumpPrice,
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
