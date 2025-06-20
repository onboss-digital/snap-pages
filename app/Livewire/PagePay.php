<?php

namespace App\Livewire;

use App\Factories\PaymentGatewayFactory; // Added
use App\Interfaces\PaymentGatewayInterface; // Added
// Removed: use GuzzleHttp\Client;
// Removed: use GuzzleHttp\Psr7\Request;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class PagePay extends Component
{

    public $cardName, $cardNumber, $cardExpiry, $cardCvv, $email, $phone, $cpf,
        $plans, $modalData, $product;

    // Modais
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
    public $bump = [
        'id' => 4,
        'title' => 'Criptografía anónima',
        'description' => 'Acesso a conteúdos ao vivo e eventos',
        'price' => 9.99,
        'hash' => '3nidg2uzc0', // This hash might be gateway-specific
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

    public function mount(PaymentGatewayInterface $paymentGateway = null) // Modified to allow injection, or resolve via factory
    {
        // If a gateway is not injected (e.g. by a service provider), create it using the factory
        $this->paymentGateway = $paymentGateway ?: PaymentGatewayFactory::create();

        $this->plans = $this->getPlans();
        $this->selectedCurrency = Session::get('selectedCurrency', 'BRL');
        $this->selectedPlan = Session::get('selectedPlan', 'monthly');
        $this->selectedLanguage = app()->getLocale();
        $this->calculateTotals();
        $this->activityCount = rand(1, 50);
        $this->product = [
            'hash' => '8v1zcpkn9j', // This hash might be gateway-specific
            'title' => 'SNAPHUBB BR',
            'cover' => 'https://d2lr0gp42cdhn1.cloudfront.net/3564404929/products/ua11qf25qootxsznxicnfdbrd',
            'product_type' => 'digital',
            'guaranted_days' => 7,
            'sale_page' => 'https://snaphubb.online',
        ];
    }

    public function getPlans()
    {
        // Plan hashes might need to be gateway-specific or mapped
        return [
            'monthly' => [
                'hash' => 'rwquocfj5c', // Gateway-specific?
                'label' => __('payment.monthly'),
                'nunber_months' => 1,
                'prices' => [
                    'BRL' => ['origin_price' => 50.00, 'descont_price' => 39.90],
                    'USD' => ['origin_price' => 10.00, 'descont_price' => 7.90],
                    'EUR' => ['origin_price' => 9.00, 'descont_price' => 6.90],
                ],
            ],
            'quarterly' => [
                'hash' => 'velit nostrud dolor in deserunt', // Gateway-specific?
                'label' => __('payment.quarterly'),
                'nunber_months' => 3,
                'prices' => [
                    'BRL' => ['origin_price' => 150.00, 'descont_price' => 109.90],
                    'USD' => ['origin_price' => 30.00, 'descont_price' => 21.90],
                    'EUR' => ['origin_price' => 27.00, 'descont_price' => 19.90],
                ],
            ],
            'semi-annual' => [
                'hash' => 'cupxl', // Gateway-specific?
                'label' => __('payment.semi-annual'),
                'nunber_months' => 6,
                'prices' => [
                    'BRL' => ['origin_price' => 300.00, 'descont_price' => 199.90],
                    'USD' => ['origin_price' => 60.00, 'descont_price' => 39.90],
                    'EUR' => ['origin_price' => 54.00, 'descont_price' => 35.90],
                ],
            ]
        ];
        // Ensure getPlans returns the full structure as before
    }

    // calculateTotals, startCheckout, rejectUpsell, acceptUpsell remain largely the same
    // but sendCheckout and prepareCheckoutData will be modified.

    public function calculateTotals()
    {
        $plan = $this->plans[$this->selectedPlan];
        // Ensure $this->selectedCurrency is valid for $plan['prices']
        if (!isset($plan['prices'][$this->selectedCurrency])) {
            // Handle error or default to a currency
            $this->selectedCurrency = 'BRL'; // Or throw an exception
        }
        $prices = $plan['prices'][$this->selectedCurrency];

        $this->totals = [
            'month_price' => $prices['origin_price'] / $plan['nunber_months'],
            'month_price_discount' => $prices['descont_price'] / $plan['nunber_months'],
            'total_price' => $prices['origin_price'],
            'total_discount' => $prices['origin_price'] - $prices['descont_price'],
        ];

        $finalPrice = $prices['descont_price'];
        if ($this->bumpActive && isset($this->bump['price'])) {
            // Ensure bump price is treated as a number
            $finalPrice += floatval($this->bump['price']);
        }
        $this->totals['final_price'] = $finalPrice;

        $this->totals = array_map(function ($value) {
            return number_format(round($value, 2), 2, ',', '.'); // Changed to round to 2 decimal places consistently
        }, $this->totals);
    }

    public function startCheckout()
    {
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
        $this->validate();
        $this->showSecure = true;
        $this->showLodingModal = true; // Assuming "Loding" is intended

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
                // Directly call sendCheckout which now uses the injected gateway
                $this->sendCheckout(); // Removed return here, sendCheckout handles redirect or error
                $this->showLodingModal = false; // Hide loading modal after sendCheckout is called
                return; // Return to prevent further execution if not an upsell case
        }
        $this->showLodingModal = false;
    }

    public function rejectUpsell()
    {
        $this->showUpsellModal = false;
        // Logic for downsell offer (quarterly)
        if ($this->selectedPlan === 'monthly') { // Only show downsell if current plan is monthly
            $offerValue = round($this->plans['quarterly']['prices'][$this->selectedCurrency]['descont_price'] / $this->plans['quarterly']['nunber_months'], 1);
            // Corrected discount calculation for downsell
            $basePriceForDiscountCalc = $this->plans['monthly']['prices'][$this->selectedCurrency]['origin_price']; // Price of the plan they *were* on
            $offerDiscont = ($basePriceForDiscountCalc * $this->plans['quarterly']['nunber_months']) - ($offerValue * $this->plans['quarterly']['nunber_months']);

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
        $this->showDownsellModal = $this->showUpsellModal = false;
        $this->showProcessingModal = true;

        $checkoutData = $this->prepareCheckoutData();

        // Use the injected payment gateway
        $response = $this->paymentGateway->processPayment($checkoutData);

        $this->showProcessingModal = false; // Hide after processing attempt

        if ($response['status'] === 'success') {
            Log::channel('payment_checkout')->info('PagePay: Payment successful via gateway.', [
                'gateway' => get_class($this->paymentGateway),
                'response' => $response
            ]);
            // Assuming a successful payment always redirects to a thank you page
            // The URL might need to be configurable or based on gateway response
            $redirectUrl = $response['redirect_url'] ?? "https://web.snaphubb.online/obg/"; // Default or from response

            // Perform the redirect. Livewire needs a specific way to do this.
            // If $this->paymentGateway->processPayment already handles redirect, this might not be needed.
            // For now, let's assume we need to redirect from PagePay.
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
            $this->showProcessingModal = false; // Ensure it's hidden on error
        }
    }

    private function prepareCheckoutData()
    {
        // This data structure was originally for TriboPay.
        // It should be made generic or adapted within each gateway.
        // For now, we keep it similar, and gateways will need to map it.

        $numeric_final_price = floatval(str_replace(',', '.', str_replace('.', '', $this->totals['final_price'])));

        $expMonth = null;
        $expYear = null;
        if ($this->cardExpiry) {
            $parts = explode('/', $this->cardExpiry);
            $expMonth = $parts[0] ?? null;
            if (!empty($parts[1])) {
                // Ensure year is 4 digits if 2 digits are provided
                $expYear = (strlen($parts[1]) == 2) ? '20' . $parts[1] : $parts[1];
            }
        }

        $cartItems = [];
        $currentPlanDetails = $this->plans[$this->selectedPlan];
        $currentPlanPriceInfo = $currentPlanDetails['prices'][$this->selectedCurrency];

        $cartItems[] = [
            'product_hash' => $currentPlanDetails['hash'], // May need to be gateway-agnostic ID
            'title' => $this->product['title'] . ' - ' . $currentPlanDetails['label'],
            // Price should be in cents, ensure correct calculation
            'price' => (int)round(floatval($currentPlanPriceInfo['descont_price']) * 100),
            'quantity' => 1,
            'operation_type' => 1, // This seems specific, might need to be handled by gateway
        ];

        if ($this->bumpActive) {
            $cartItems[] = [
                'product_hash' => $this->bump['hash'], // May need to be gateway-agnostic ID
                'title' => $this->bump['title'],
                'price' => (int)round(floatval($this->bump['price']) * 100),
                'quantity' => 1,
                'operation_type' => 2, // Specific
            ];
        }

        // Basic customer data
        $customerData = [
            'name' => $this->cardName,
            'email' => $this->email,
            'phone_number' => preg_replace('/[^0-9+]/', '', $this->phone), // Clean phone
        ];
        if ($this->selectedCurrency === 'BRL' && $this->cpf) {
            $customerData['document'] = preg_replace('/\D/', '', $this->cpf); // Clean CPF
        }

        // Basic card data
        $cardDetails = [
            'number' => $this->cardNumber,
            'holder_name' => $this->cardName,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'cvv' => $this->cardCvv,
        ];
         if ($this->selectedCurrency === 'BRL' && $this->cpf) {
            $cardDetails['cpf'] = preg_replace('/\D/', '', $this->cpf); // Include cleaned CPF for card if needed by gateway
        }


        return [
            'amount' => (int)round($numeric_final_price * 100), // Total amount in cents
            'currency_code' => $this->selectedCurrency, // Added currency code
            'offer_hash' => $currentPlanDetails['hash'], // This is likely gateway-specific
            'payment_method_type' => 'credit_card', // Could be more dynamic in future

            'card' => $cardDetails,
            'customer' => $customerData,
            'cart' => $cartItems,

            'installments' => 1, // Default, might need to be dynamic
            // Add other potentially useful generic data:
            'selected_plan_key' => $this->selectedPlan,
            'language' => $this->selectedLanguage,
            'metadata' => [ // For any other custom data
                'product_main_hash' => $this->product['hash'],
                'bump_active' => $this->bumpActive,
            ]
        ];
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
        // If they reject downsell, they proceed with the original plan they had *before* upsell/downsell sequence.
        // This needs to ensure the selectedPlan is correctly set to what it was.
        // For simplicity now, assuming it proceeds with the plan that was active when startCheckout was first called.
        // If startCheckout was for 'monthly', and they rejected upsell (to semi-annual),
        // then rejected downsell (to quarterly), they should be paying for 'monthly'.
        // The current `selectedPlan` would be 'monthly' in this scenario if not changed by acceptDownsell.
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
        // Ensure plans and totals are up-to-date for rendering, especially if currency/plan changes outside of direct wire:model actions
        // $this->plans = $this->getPlans(); // May not be needed if mount and changeLanguage cover it
        // $this->calculateTotals(); // May not be needed if mount and changeLanguage cover it

        return view('livewire.page-pay')->layoutData([
            'title' => __('payment.title'),
            'canonical' => url()->current(),
        ]);
    }
}
