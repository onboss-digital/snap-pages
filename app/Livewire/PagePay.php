<?php

namespace App\Livewire;

use App\Factories\PaymentGatewayFactory;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\PixOrder;
use App\Services\PaymentGateways\MercadoPagoGateway;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PagePay extends Component
{
    // Propriedades para o formulário de Cartão de Crédito
    public $cardName, $email, $phone, $cpf, $cardNumber, $cardExpiry, $cardCvv;
    public $paymentMethodId;

    // Propriedades para o formulário PIX
    public $pixName, $pixEmail, $pixCpf;

    // Estado do Componente
    public $plans, $product, $testimonials = [];
    public $selectedPaymentMethod = 'credit_card';

    // Modals
    public $showErrorModal = false;
    public $showProcessingModal = false;
    public $showSuccessModal = false;
    public $showDownsellModal = false;
    public $showPixModal = false;
    public $pixResult = [];

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
            'cardName' => 'required_if:selectedPaymentMethod,credit_card|string|max:255',
            'email' => 'required_if:selectedPaymentMethod,credit_card|email',

            'pixName' => 'required_if:selectedPaymentMethod,pix|string|max:255',
            'pixEmail' => 'required_if:selectedPaymentMethod,pix|email',
            'pixCpf' => 'required_if:selectedPaymentMethod,pix|string', // Simplificando a validação do CPF por enquanto
        ];

        if ($this->selectedLanguage === 'br') {
            $rules['cpf'] = ['required_if:selectedPaymentMethod,credit_card', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}\-\d{2}$|^\d{11}$/'];
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

    public function generatePixOrder()
    {
        $this->selectedPaymentMethod = 'pix';
        $this->validate([
            'pixName' => 'required|string|max:255',
            'pixEmail' => 'required|email',
            'pixCpf' => 'required|string', // Adicionar uma validação de CPF mais robusta depois
        ]);

        $this->showProcessingModal = true;

        try {
            // 1. Criar o pedido no nosso banco de dados com status "pending"
            $order = PixOrder::create([
                'transaction_amount' => 24.90, // Valor mockado conforme solicitado
                'product_description' => 'Produto Teste PIX', // Descrição mockada
                'customer_name' => $this->pixName,
                'customer_email' => $this->pixEmail,
                'customer_document' => $this->pixCpf,
                'status' => 'pending',
            ]);

            // 2. Preparar dados para o gateway de pagamento
            $nameParts = explode(' ', $this->pixName, 2);
            $paymentData = [
                'transaction_amount' => $order->transaction_amount,
                'product_description' => $order->product_description,
                'payer' => [
                    'email' => $this->pixEmail,
                    'first_name' => $nameParts[0],
                    'last_name' => $nameParts[1] ?? '',
                    'cpf' => $this->pixCpf,
                ],
            ];

            // 3. Chamar o gateway para gerar o PIX
            $gateway = new MercadoPagoGateway();
            $gatewayResponse = $gateway->generatePix($paymentData);

            // 4. Atualizar nosso pedido com os dados do Mercado Pago
            $order->update([
                'mercado_pago_id' => $gatewayResponse['mercado_pago_id'],
                'qr_code_base64' => $gatewayResponse['qr_code_base64'],
                'qr_code' => $gatewayResponse['qr_code'],
            ]);

            // 5. Preparar o resultado para ser exibido no modal
            $this->pixResult = [
                'qr_code_base64' => $gatewayResponse['qr_code_base64'],
                'qr_code' => $gatewayResponse['qr_code'],
                'status' => 'pending',
                'order_id' => $order->id, // Passar o ID para a verificação de status
            ];

        } catch (\Exception $e) {
            $this->showErrorModal = true;
            $this->addError('pix_payment', 'Não foi possível gerar o PIX. Tente novamente.');
            Log::error('Erro ao gerar PIX com Mercado Pago.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->showProcessingModal = false;
        }
    }

    public function checkPixStatus()
    {
        if (isset($this->pixResult['order_id'])) {
            $order = PixOrder::find($this->pixResult['order_id']);
            if ($order && $order->status === 'approved') {
                $this->pixResult['status'] = 'approved';

                // Opcional: redirecionar ou mostrar uma mensagem de sucesso mais proeminente.
                // Por enquanto, apenas atualizamos o status para o modal reagir.
            }
        }
    }

    public function render()
    {
        return view('livewire.page-pay')->layoutData(['title' => __('payment.title')]);
    }
}
