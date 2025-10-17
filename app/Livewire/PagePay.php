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

    // Propriedades para o formulário PIX (independentes)
    public $pixName, $pixEmail, $pixPhone, $pixCpf;

    // Estado do Componente
    public $plans, $product, $testimonials = [];
    public $selectedPaymentMethod = 'credit_card';
    public $pixData = null;
    public $pixStatus = null;

    // Modals
    public $showErrorModal = false;
    public $showProcessingModal = false;
    public $showPixModal = false;

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
        if ($this->selectedPaymentMethod === 'credit_card') {
            $rules = [
                'cardName' => 'required|string|max:255',
                'email' => 'required|email',
            ];
            if ($this->selectedLanguage === 'br') {
                $rules['cpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
            }
        } elseif ($this->selectedPaymentMethod === 'pix') {
            $rules = [
                'pixName' => 'required|string|max:255',
                'pixEmail' => 'required|email',
            ];
            if ($this->selectedLanguage === 'br') {
                $rules['pixCpf'] = ['required', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
            }
        } else {
            $rules = [];
        }
        return $rules;
    }

    public function mount()
    {
        Log::channel('pix_payment')->info('Componente PagePay montado com sucesso.');

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
        Log::channel('pix_payment')->info('Checkout iniciado.', ['payment_method' => $this->selectedPaymentMethod]);
        $this->showProcessingModal = true;

        try {
            Log::channel('pix_payment')->info('A iniciar a validação.');
            $this->validate();
            Log::channel('pix_payment')->info('Validação bem-sucedida.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->showProcessingModal = false;
            Log::channel('pix_payment')->error('Erro de validação.', ['errors' => $e->errors()]);
            return;
        }

        try {
            $this->sendCheckout();
        } catch (\Exception $e) {
            $this->showProcessingModal = false;
            $this->showErrorModal = true;
            $this->addError('pix', 'Não foi possível comunicar com o provedor de pagamento. Tente novamente.');
            Log::channel('pix_payment')->error('Exceção não tratada durante o checkout.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function sendCheckout()
    {
        $checkoutData = $this->prepareCheckoutData();
        $this->paymentGateway = PaymentGatewayFactory::create('abacatepay');
        $response = $this->paymentGateway->processPayment($checkoutData);

        if ($this->selectedPaymentMethod === 'pix') {
            if ($response['status'] === 'success') {
                $this->pixData = $response['data'];
                $this->pixStatus = $response['data']['status'] ?? 'PENDING';
                $this->dispatch('pix-generated');
                Log::channel('pix_payment')->info('PIX criado com sucesso.', ['pix_id' => $this->pixData['pix_id']]);
            } else {
                $this->showErrorModal = true;
                $this->addError('pix', $response['message'] ?? 'Ocorreu um erro ao gerar o PIX.');
            }
        }
        $this->showProcessingModal = false;
    }

    private function prepareCheckoutData()
    {
        $numeric_final_price = floatval(str_replace(',', '.', str_replace('.', '', $this->totals['final_price'])));

        if ($this->selectedPaymentMethod === 'pix') {
            $customerData = [
                'name' => $this->pixName,
                'email' => $this->pixEmail,
                'phone_number' => $this->pixPhone,
                'document' => $this->pixCpf,
            ];
        } else {
            $customerData = [
                'name' => $this->cardName,
                'email' => $this->email,
                'phone_number' => $this->phone,
                'document' => $this->cpf,
            ];
        }

        $baseData = [
            'amount' => (int)round($numeric_final_price * 100),
            'currency_code' => $this->selectedCurrency,
            'payment_method' => $this->selectedPaymentMethod,
            'customer' => $customerData,
        ];

        if ($this->selectedPaymentMethod === 'pix') {
            $baseData['expiresIn'] = config('services.abacatepay.pix_expiration', 1800);
        }

        return $baseData;
    }

    public function openPixModal()
    {
        $this->selectedPaymentMethod = 'pix';
        $this->showPixModal = true;
    }

    public function switchToCard()
    {
        $this->selectedPaymentMethod = 'credit_card';
        $this->showPixModal = false;
        $this->pixData = null;
        $this->pixStatus = null;
    }

    public function closeModal()
    {
        $this->showErrorModal = false;
        $this->showPixModal = false;
        if ($this->selectedPaymentMethod === 'pix' && !$this->pixData) {
            $this->selectedPaymentMethod = 'credit_card';
        }
    }

    public function changeLanguage($lang)
    {
        if (array_key_exists($lang, $this->availableLanguages)) {
            session(['locale' => $lang]);
            app()->setLocale($lang);
            $this->selectedLanguage = $lang;
            $this->selectedCurrency = $lang === 'br' ? 'BRL' : 'USD';
            
            if ($lang !== 'br' && $this->selectedPaymentMethod === 'pix') {
                $this->switchToCard();
            }
            
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
                if ($this->pixStatus === 'PAID') $this->dispatch('pix-paid');
                elseif (in_array($this->pixStatus, ['EXPIRED', 'FAILED'])) $this->dispatch('pix-failed');
            }
        } catch (\Exception $e) {
            Log::channel('pix_payment')->error('Erro ao verificar status do PIX.', [
                'pix_id' => $this->pixData['pix_id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.page-pay')->layoutData(['title' => __('payment.title')]);
    }
}
