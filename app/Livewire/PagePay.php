<?php

namespace App\Livewire;

use App\Factories\PaymentGatewayFactory;
use App\Interfaces\PaymentGatewayInterface;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PagePay extends Component
{
    // Propriedades para o formulário de Cartão de Crédito
    public $cardName, $email, $phone, $cpf, $cardNumber, $cardExpiry, $cardCvv;
    public $paymentMethodId;

    // Estado do Componente
    public $plans, $product, $testimonials = [];
    public $selectedPaymentMethod = 'credit_card';

    // Modals
    public $showErrorModal = false;
    public $showProcessingModal = false;

    // Configurações de Idioma e Moeda
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

    // Outras propriedades
    public $totals = [];
    private PaymentGatewayInterface $paymentGateway;
    public $countdownMinutes = 15;
    public $countdownSeconds = 0;
    public $spotsLeft = 12;
    public $activityCount = 0;
    public $showSecure = false;
    public $spotsLeft = 12;

    protected function rules()
    {
        $rules = [
            'cardName' => 'required|string|max:255',
            'email' => 'required|email',
        ];

        if ($this->selectedLanguage === 'br') {
            $rules['cpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
        }

        return $rules;
    }

    public function mount()
    {
        if (!Session::has('locale_detected')) {
            $this->detectLanguage();
            Session::put('locale_detected', true);
        } else {
            $this->selectedLanguage = session('locale', 'br');
            app()->setLocale($this->selectedLanguage);
        }

        $this->testimonials = trans('checkout.testimonials');
        $this->plans = $this->getPlans();
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->calculateTotals();

        if (isset($this->plans[$this->selectedPlan])) {
            $this->product = [
                'hash' => $this->plans[$this->selectedPlan]['hash'] ?? null,
                'title' => $this->plans[$this->selectedPlan]['label'] ?? '',
            ];
        }
    }

    public function getPlans()
    {
        $localPlans = config('plans');
        $formattedPlans = [];

        foreach ($localPlans as $key => $plan) {
            $formattedPlans[$key] = [
                'hash' => $plan['id'],
                'label' => $plan['label'],
                'nunber_months' => 1,
                'prices' => [
                    $this->selectedCurrency => [
                        'origin_price' => $plan['original_price'] / 100,
                        'descont_price' => $plan['price'] / 100,
                    ],
                ],
            ];
        }
        return $formattedPlans;
    }

    public function calculateTotals()
    {
        $plan = $this->plans[$this->selectedPlan] ?? null;
        if (!$plan) {
            Log::error('Plano selecionado não encontrado.', ['selected_plan' => $this->selectedPlan]);
            return;
        }

        $prices = $plan['prices'][$this->selectedCurrency] ?? null;
        if (!$prices) {
            Log::error('Preços não encontrados para a moeda selecionada.', ['plan' => $this->selectedPlan, 'currency' => $this->selectedCurrency]);
            return;
        }

        $this->totals = [
            'total_price' => $prices['origin_price'],
            'total_discount' => $prices['origin_price'] - $prices['descont_price'],
            'final_price' => $prices['descont_price'],
        ];

        $this->totals = array_map(fn($value) => number_format(round($value, 2), 2, ',', '.'), $this->totals);
    }

    public function startCheckout()
    {
        $this->showProcessingModal = true;

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->showProcessingModal = false;
            return;
        }

        try {
            $this->sendCheckout();
        } catch (\Exception $e) {
            $this->showProcessingModal = false;
            $this->showErrorModal = true;
            $this->addError('payment', 'Não foi possível comunicar com o provedor de pagamento. Tente novamente.');
            Log::error('Exceção não tratada durante o checkout.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function sendCheckout()
    {
        $checkoutData = $this->prepareCheckoutData();

        // A lógica do gateway de pagamento precisa ser ajustada aqui
        // $this->paymentGateway = PaymentGatewayFactory::create('default_gateway');
        // $response = $this->paymentGateway->processPayment($checkoutData);

        // Lógica de resposta (exemplo)
        // if ($response['status'] === 'success') {
        //     // sucesso
        // } else {
        //     $this->showErrorModal = true;
        //     $this->addError('payment', $response['message'] ?? 'Ocorreu um erro no pagamento.');
        // }

        $this->showProcessingModal = false;
    }

    private function prepareCheckoutData()
    {
        $numeric_final_price = floatval(str_replace(',', '.', str_replace('.', '', $this->totals['final_price'])));

        $customerData = [
            'name' => $this->cardName,
            'email' => $this->email,
            'phone_number' => $this->phone,
            'document' => $this->cpf,
        ];

        return [
            'amount' => (int)round($numeric_final_price * 100),
            'currency_code' => $this->selectedCurrency,
            'payment_method' => $this->selectedPaymentMethod,
            'customer' => $customerData,
        ];
    }

    public function closeModal()
    {
        $this->showErrorModal = false;
    }

    public function changeLanguage($lang)
    {
        if (array_key_exists($lang, $this->availableLanguages)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);
            $this->selectedLanguage = $lang;
            $this->selectedCurrency = $lang === 'br' ? 'BRL' : 'USD';
            
            $this->plans = $this->getPlans();
            $this->testimonials = trans('checkout.testimonials');
            $this->calculateTotals();
        }
    }

    private function detectLanguage()
    {
        $preferredLanguage = request()->getPreferredLanguage(array_keys($this->availableLanguages));
        $this->changeLanguage(str_starts_with($preferredLanguage, 'pt') ? 'br' : 'en');
    }

    public function render()
    {
        return view('livewire.page-pay')->layoutData(['title' => __('payment.title')]);
    }
}
