@section('head')
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/ico" href="{{ asset('imgs/mini_logo.png') }}" />

@php
$gateway = config('services.default_payment_gateway', 'stripe');
@endphp

<style>
    body {
        font-family: 'Urbanist', sans-serif;
        background-color: #121212;
        color: white;
    }

    .animate-fade {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .sticky-summary {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 50;
        transform: translateY(0);
        /* Always visible on mobile */
        transition: none;
    }
</style>
<script type="text/javascript">
    (function(c, l, a, r, i, t, y) {
        c[a] = c[a] || function() {
            (c[a].q = c[a].q || []).push(arguments)
        };
        t = l.createElement(r);
        t.async = 1;
        t.src = "https://www.clarity.ms/tag/" + i;
        y = l.getElementsByTagName(r)[0];
        y.parentNode.insertBefore(t, y);
    })(window, document, "clarity", "script", "rtcb4op3g8");
</script>
<!-- Meta Pixel Code -->
<script>
    ! function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '491262282523520');
    fbq('track', 'InitiateCheckout');
</script>
<noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id=491262282523520&ev=InitiateCheckout&noscript=1" /></noscript>
<!-- End Meta Pixel Code -->

<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-VYXG6DL5W4"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-VYXG6DL5W4');
</script>
<!-- End Google Analytics -->
@endsection

@section('scripts')
@endsection


<div>
    <div class="container mx-auto px-4 py-8 max-w-4xl pb-24 md:pb-8">

        <!-- Header -->
        <header class="mb-8">
            <div class="flex flex-col md:flex-row items-center justify-between">

                @php
                $logo = asset('imgs/logo.png');
                @endphp


                <img class="img-fluid logo" src="{{ $logo }}" alt="streamit">

                <!-- Language Selector -->
                <div class="flex items-center space-x-4">
                    <div class="relative text-sm">
                        <form id="language-form" method="POST">
                            @csrf
                            <select id="language-selector" name="lang"
                                wire:change="changeLanguage(event.target.value)"
                                class="bg-[#1F1F1F] text-white rounded-md px-3 py-1.5 border border-gray-700 appearance-none pr-8 focus:outline-none focus:ring-1 focus:ring-[#E50914] hover:border-gray-500 transition-all">
                                @foreach ($availableLanguages as $code => $name)
                                <option value="{{ $code }}"
                                    {{ app()->getLocale() == $code ? 'selected' : '' }}>
                                    {!! $name !!}</option>
                                @endforeach
                            </select>
                        </form>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 w-full rounded-xl overflow-hidden bg-gray-900">
                <!-- Desktop Banner -->
                <img class="hidden md:block w-full" src="{{ __('checkout.banner_desktop') }}" alt="Promotional Banner">
                <!-- Mobile Banner -->
                <img class="block md:hidden w-full" src="{{ __('checkout.banner_mobile') }}" alt="Promotional Banner">
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-white mt-6 text-center">
                {{ __('checkout.main_title') }}
            </h1>
            <p class="text-lg text-gray-300 mt-2 text-center">{{ __('checkout.subtitle') }}</p>
        </header>
        <form accept="" method="POST" id="payment-form">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2">
                    <!-- Currency Selector -->
                    {{-- <div class="bg-[#1F1F1F] rounded-xl p-6 mb-6">
                        <h2 class="text-xl font-semibold text-white mb-4">{{ __('payment.select_currency') }}</h2>

                    <div class="relative">
                        <select id="currency-selector" name="currency" wire:model="selectedCurrency"
                            wire:change="calculateTotals"
                            class="w-full bg-[#2D2D2D] text-white rounded-lg p-4 border border-gray-700 appearance-none pr-10 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all">
                            @foreach ($currencies as $code => $currency)
                            <option value="{{ $code }}"
                                {{ $selectedCurrency == $code ? 'selected' : '' }}>
                                {{ __($currency['label']) }}
                            </option>
                            @endforeach
                        </select>
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-white">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                            </svg>
                        </div>
                    </div>
                </div> --}}

                <!-- Benefits -->
                <div class="bg-[#1F1F1F] rounded-xl p-6 mb-6">
                    <h2 class="text-xl font-semibold text-white mb-4">{{ __('checkout.benefits_title') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="benefits-container">
                        @foreach (__('checkout.benefits') as $key => $description)
                            @if (str_ends_with($key, '_desc')) @continue @endif
                            <div class="flex items-start space-x-3">
                                <div class="p-2 bg-[#E50914] rounded-lg">
                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-white">{{ $description }}</h3>
                                    <p class="text-sm text-gray-400">{{ __('checkout.benefits.' . $key . '_desc') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                <!-- Payment Methods -->
                <div id="payment-method-section" class="bg-[#1F1F1F] rounded-xl p-6 mb-6 scroll-mt-8">
                    {{-- ===== MÉTODO DE PAGAMENTO ===== --}}
<div class="mb-6">
    <h3 class="text-xl font-bold text-white mb-4">Método de pagamento</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Card Cartão de Crédito --}}
        <div
            wire:click="$set('selectedPaymentMethod', 'credit_card')"
            class="payment-method-card cursor-pointer p-6 rounded-lg border-2 transition-all duration-300
                {{ $selectedPaymentMethod === 'credit_card' ? 'border-red-500 bg-gray-800' : 'border-gray-700 bg-gray-900 hover:border-gray-600' }}"
        >
            <div class="flex flex-col items-center text-center">
                <svg class="w-12 h-12 mb-3 {{ $selectedPaymentMethod === 'credit_card' ? 'text-red-500' : 'text-gray-400' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span class="text-lg font-semibold {{ $selectedPaymentMethod === 'credit_card' ? 'text-white' : 'text-gray-300' }}">
                    Cartão
                </span>
            </div>
        </div>

        {{-- Card PIX (só para Brasil) --}}
        @if($selectedLanguage === 'br')
        <div
            wire:click="$set('selectedPaymentMethod', 'pix')"
            class="payment-method-card cursor-pointer p-6 rounded-lg border-2 transition-all duration-300
                {{ $selectedPaymentMethod === 'pix' ? 'border-green-500 bg-gray-800' : 'border-gray-700 bg-gray-900 hover:border-gray-600' }}"
        >
            <div class="flex flex-col items-center text-center">
                <svg class="w-12 h-12 mb-3 {{ $selectedPaymentMethod === 'pix' ? 'text-green-500' : 'text-gray-400' }}"
                     fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5zm0 18c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                </svg>
                <span class="text-lg font-semibold {{ $selectedPaymentMethod === 'pix' ? 'text-white' : 'text-gray-300' }}">
                    PIX
                </span>
            </div>
        </div>
        @endif
    </div>

</div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.expiry_date') }}</label>
                                        <input name="card_expiry" type="text" id="card-expiry" x-mask="99/99"
                                            placeholder="MM/YY" wire:model.defer="cardExpiry"
                                            class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                        @error('cardExpiry')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.security_code') }}</label>
                                        <input name="card_cvv" type="text" id="card-cvv" placeholder="CVV"
                                            x-mask="9999" wire:model.defer="cardCvv"
                                            class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                        @error('cardCvv')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                @else
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('checkout.card_number') }}</label>
                                    <div id="card-element" class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" wire:ignore></div>
                                    <div id="card-errors"></div>
                                    <input name="payment_method_id" type="hidden" wire:model.defer="paymentMethodId" id="payment-method-id">
                                </div>
                                @endif

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.card_name') }}</label>
                                    <input name="card_name" type="text"
                                        placeholder="{{ __('payment.card_name') }}" wire:model="cardName"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    @error('cardName')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.email') }}</label>
                                    <input name="email" type="text" placeholder="{{ __('payment.email') }}"
                                        wire:model="email"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    <p class="text-xs text-gray-400 mt-1 pl-1">{{ __('checkout.email_helper') }}</p>
                                    @error('email')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.phone') }}</label>
                                    <input name="phone" type="text" id="phone"
                                        placeholder="+1 (555) 123-4567"
                                        wire:model.defer="phone"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    <p class="text-xs text-gray-400 mt-1 pl-1">{{ __('checkout.phone_helper') }}</p>
                                    @error('phone')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            @else
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-300 mb-1">{{ __('checkout.card_number') }}</label>
                                <div id="card-element" class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" wire:ignore></div>
                                <div id="card-errors"></div>
                                <input name="payment_method_id" type="hidden" wire:model.defer="paymentMethodId" id="payment-method-id">
                            </div>
                            @endif

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.card_name') }}</label>
                                <input name="card_name" type="text"
                                    placeholder="{{ __('payment.card_name') }}" wire:model.defer="cardName"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                @error('cardName')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
                                <input name="email" type="email" placeholder="seu@email.com"
                                    wire:model.defer="email"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                @error('email')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.phone') }}</label>
                                <input name="phone" type="tel" placeholder="+55 (11) 99999-9999"
                                    wire:model.defer="phone"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                @error('phone')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                                @if($selectedLanguage === 'br')
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.cpf') }}</label>
                                    <input name="cpf" type="text" id="cpf" placeholder="000.000.000-00"
                                        x-mask="999.999.999-99" wire:model.defer="cpf"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    @error('cpf')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Order Bumps -->
                    @if(!empty($bumps))
                    <div class="bg-[#1F1F1F] rounded-xl p-5 border border-gray-700">
                        @foreach ($bumps as $index => $bump)
                        <div class="flex items-start mb-4 last:mb-0">
                            <div class="flex items-center h-5">
                                <input
                                    id="order-bump-{{ $bump['id'] }}"
                                    type="checkbox"
                                    class="w-5 h-5 text-[#E50914] bg-[#2D2D2D] border-gray-600 rounded
                               focus:ring-[#E50914] focus:ring-opacity-25 focus:ring-2
                               focus:border-[#E50914] cursor-pointer"
                                    wire:model="bumps.{{ $index }}.active"
                                    wire:change="calculateTotals" />
                            </div>
                            <label for="order-bump-{{ $bump['id'] }}" class="ml-3 cursor-pointer">
                                <div class="text-white text-base font-semibold flex items-center">
                                    <svg class="h-5 w-5 text-[#E50914] mr-1" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ $bump['title'] }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.your_name') }}</label>
                                <input name="pix_name" type="text"
                                    placeholder="{{ __('payment.your_name_placeholder') }}" wire:model.defer="cardName"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-green-500 transition-all" />
                                @error('cardName')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
                                <input name="pix_email" type="email"
                                    placeholder="seu@email.com" wire:model.defer="email"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-green-500 transition-all" />
                                @error('email')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Telefone</label>
                                <input name="pix_phone" type="tel"
                                    placeholder="+55 (11) 99999-9999" wire:model.defer="phone"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-green-500 transition-all" />
                                @error('phone')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">CPF (obrigatório para pagamentos no Brasil)</label>
                                <input name="pix_cpf" type="text" x-mask="999.999.999-99"
                                    placeholder="000.000.000-00" wire:model.defer="cpf"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-green-500 transition-all" />
                                @error('cpf')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        @else
                        <!-- Se já gerou o PIX, mostra o card do PIX -->
                        @if ($pixData)
                            <x-pix-card
                                :pixData="$pixData"
                                :pixStatus="$pixStatus"
                                :expiresAt="$pixData['expires_at'] ?? null"
                            />
                        @endif
                        @endif

                    </div>
                    @endif
                </div>

                <!-- Order Bumps -->
                @if(!empty($bumps))
                <div class="bg-[#1F1F1F] rounded-xl p-5 border border-gray-700">
                    @foreach ($bumps as $index => $bump)
                    <div class="flex items-start mb-4 last:mb-0">
                        <div class="flex items-center h-5">
                            <input
                                id="order-bump-{{ $bump['id'] }}"
                                type="checkbox"
                                class="w-5 h-5 text-[#E50914] bg-[#2D2D2D] border-gray-600 rounded
                           focus:ring-[#E50914] focus:ring-opacity-25 focus:ring-2
                           focus:border-[#E50914] cursor-pointer"
                                wire:model="bumps.{{ $index }}.active"
                                wire:change="calculateTotals" />
                        </div>
                        <label for="order-bump-{{ $bump['id'] }}" class="ml-3 cursor-pointer">
                            <div class="text-white text-base font-semibold flex items-center">
                                <svg class="h-5 w-5 text-[#E50914] mr-1" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ $bump['title'] }}
                            </div>
                            <p class="text-gray-400 text-sm mt-1">{{ $bump['description'] }}</p>
                            <p class="text-[#E50914] font-medium mt-2">
                                +{{ $currencies[$selectedCurrency]['symbol'] }} {{ number_format($bump['price'], 2, ',', '.') }}
                            </p>
                        </label>
                    </div>
                    @endforeach
                </div>
                @endif




                <!-- Testimonials -->
                <div class="bg-transparent rounded-xl">
                    <h2 class="text-2xl font-bold text-white mb-6 text-center">{{ __('checkout.testimonials_title') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @if(is_array($testimonials) && !empty($testimonials))
                            @foreach (array_slice($testimonials, 0, 4) as $testimonial)
                                <div class="bg-gray-800 bg-opacity-50 p-6 rounded-xl border border-gray-700 shadow-lg flex flex-col h-full transform transition-transform hover:scale-105">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center mb-4">
                                            <img src="https://ui-avatars.com/api/?name={{ substr($testimonial['name'], 0, 1) }}&color=FFFFFF&background=E50914&bold=true&size=48" alt="Avatar" class="w-12 h-12 rounded-full mr-4 border-2 border-red-500">
                                            <div>
                                                <p class="font-bold text-white text-lg">{{ $testimonial['name'] }}</p>
                                                <div class="flex text-yellow-400 mt-1">
                                                    @for ($i = 0; $i < 5; $i++)
                                                        <svg class="w-5 h-5" fill="{{ $i < $testimonial['stars'] ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.519 4.674c.3.921-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.519-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative flex-grow">
                                            <svg class="absolute top-0 left-0 w-8 h-8 text-gray-600 transform -translate-x-2 -translate-y-2" fill="currentColor" viewBox="0 0 24 24"><path d="M6.5 10c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zm11 0c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5zM20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 14c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z" opacity=".2"/></svg>
                                            <p class="text-gray-300 text-base italic pl-4 border-l-4 border-red-500">"{{ $testimonial['quote'] }}"</p>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="md:col-span-1">
                    <div class="bg-[#1F1F1F] rounded-xl p-6 sticky top-6" wire:poll.1s="decrementTimer"
                        wire:poll.15000ms="decrementSpotsLeft" wire:poll.8000ms="updateLiveActivity">
                        <h2 class="text-xl font-semibold text-white mb-4 text-center">{{ __('checkout.order_summary_title') }}</h2>

                        <!-- Timer -->
                        <div class="bg-gray-800 border border-red-600 rounded-lg p-3 mb-6 flex items-center justify-center animate-pulse">
                            <svg class="w-6 h-6 text-red-600 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-white font-medium">{{ __('checkout.offer_expires_in') }} <span id="countdown-timer"
                                    class="font-bold text-red-500 tracking-wider">{{ sprintf('%02d:%02d', $countdownMinutes, $countdownSeconds) }}</span></span>
                        </div>

                        <!-- Plan selection -->
                        <div class="mb-6">
                            <label
                                class="block text-sm font-medium text-gray-300 mb-2">{{ __('payment.select_plan') }}</label>

                            <div class="relative">
                                <select id="plan-selector" name="plan"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 appearance-none pr-10 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all cursor-pointer"
                                    wire:model="selectedPlan" wire:change="calculateTotals">
                                    @foreach ($plans as $value => $plan)
                                    <option value="{{ $value }}">{{ $plan['label'] }}</option>
                                    @endforeach
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-white">
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                        <path d="M7 7l3-3 3 3m0 6l-3 3-3-3" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" fill="none"></path>
                                    </svg>
                                </div>
                            </div>

                            <p class="text-gray-400 text-sm mt-2">{{ __('payment.flexible_plan') }}</p>
                        </div>

                        <!-- Price anchor -->
                        <div class="mb-2 text-center">
                            <del class="text-gray-400 text-sm">{{ $currencies[$selectedCurrency]['symbol'] }}
                                {{ $totals['month_price'] ?? '00' }}</del>
                            <span class="text-green-400 text-lg font-bold ml-2"
                                id="current-price">{{ $currencies[$selectedCurrency]['symbol'] }}
                                {{ $totals['month_price_discount'] ?? '00' }}{{ __('payment.per_month') }}</span>
                        </div>

                    <!-- Price breakdown -->
                    <div class="border-t border-gray-700 pt-5 my-4 space-t-2">
                        <!-- Coupon area -->
                        {{-- <div>
                                <div class="flex space-x-2">
                                    <input name="coupon_code" type="text" id="coupon-input"
                                        placeholder="{{ __('payment.coupon_code') }}"
                        class="w-2/3 bg-[#2D2D2D] text-white rounded-lg p-2 text-sm border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                        <button type="button" id="apply-coupon"
                            class="w-1/3 bg-gray-700 hover:bg-gray-600 text-white text-sm py-2 px-2 rounded-lg transition-all">
                            {{ __('payment.apply') }}
                        </button>
                    </div>
                    <div id="coupon-message" class="text-xs mt-1 text-green-400 hidden">
                        {{ __('payment.coupon_success') }}
                    </div>
                </div> --}}
            </div>


                <!-- Resumo Final com Descontos -->
                <div id="priceSummary" class="mb-6 p-4 bg-gray-800 rounded-lg text-white">
                    <p class="text-base font-semibold mb-2">{{ __('payment.final_summary') }}</p>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ __('payment.original_price') }}</span>
                        <del class="text-gray-400">{{ $currencies[$selectedCurrency]['symbol'] }}
                            {{ $totals['total_price'] ?? '00' }}</del>
                    </div>
                    <div class="flex justify-between text-mb mb-1">
                        <span>{{ __('payment.discount') }}</span>
                        <span class="text-green-400"
                            id="discount-amount">{{ $currencies[$selectedCurrency]['symbol'] }}
                            {{ $totals['total_discount'] ?? '00' }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold mt-2 pt-2 border-t border-gray-700">
                        <span>{{ __('payment.total_to_pay') }}</span>
                        <span id="final-price">
                            {{ $currencies[$selectedCurrency]['symbol'] }}
                            {{ $totals['final_price'] ?? '00' }}</span>
                        <input type="hidden" id="input-final-price" name="final-price" value="" />
                    </div>
                </div>

                <!-- Limited spots -->
                <div class="bg-[#2D2D2D] rounded-lg p-3 mb-4 text-center">
                    <span class="font-medium">{{ __('checkout.remaining_vacancies') }}: <strong><span
                                id="spots-left">{{ $spotsLeft }}</span></strong></span>
                </div>

                <!-- Live Activity Indicator -->
                <div class="bg-[#2D2D2D] rounded-lg p-3 mb-6 text-center">
                    <span class="text-gray-400">{!! __('checkout.live_activity', ['count' => '<strong id="activityCounter" class="text-white">'.$activityCount.'</strong>']) !!}</span>
                </div>

                <!-- Verificação de Ambiente Seguro -->
                <div id="seguranca"
                    class="w-full bg-gray-800 p-4 rounded-lg flex items-center gap-3 text-sm text-gray-300 animate-pulse mb-4 @if (!$showSecure) hidden @endif">
                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M2.003 5.884L10 2l7.997 3.884v4.632c0 5.522-3.936 10.74-7.997 11.484-4.061-.744-7.997-5.962-7.997-11.484V5.884z" />
                    </svg>
                    {{ __('payment.checking_secure') }}
                </div>

                <button id="checkout-button" type="button" wire:click.prevent="startCheckout"
                    class="w-full bg-[#E50914] hover:bg-[#B8070F] text-white py-3 text-lg font-bold rounded-xl transition-all block cursor-pointer transform hover:scale-105">
                    {{ __('checkout.cta_button') }}
                </button>

                <!-- Trust badges -->
                <div class="mt-4 flex flex-col items-center space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-green-500">✅</span>
                        <span class="text-sm text-gray-300">{{ __('payment.7_day_guarantee') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-green-500">🔒</span>
                        <span class="text-sm text-gray-300">{{ __('payment.secure_ssl') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-green-500">🔁</span>
                        <span class="text-sm text-gray-300">{{ __('payment.easy_cancel') }}</span>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="text-sm text-gray-300">{{ __('payment.anonymous') }}</span>
                    </div>

                    <div class="flex justify-center space-x-3 text-xs text-gray-500">
                        <a href="#"
                            class="hover:text-gray-300 transition-colors">{{ __('payment.terms') }}</a>
                        <a href="#"
                            class="hover:text-gray-300 transition-colors">{{ __('payment.privacy') }}</a>
                        <a href="#"
                            class="hover:text-gray-300 transition-colors">{{ __('payment.support') }}</a>
                    </div>
                </div>
        </div>
    </div>
    </div>

    <!-- Sticky Summary -->
    <div id="sticky-summary" class="sticky-summary bg-[#1F1F1F] border-t border-gray-700 md:hidden p-4">
        <div class="container mx-auto flex flex-col items-center justify-center gap-2">
            <button type="button" id="sticky-checkout-button"
                wire:click.prevent="startCheckout"
                class="bg-[#E50914] hover:bg-[#B8070F] text-white py-2 px-6 text-base font-semibold rounded-full shadow-lg w-auto min-w-[180px] max-w-xs mx-auto transition-all flex items-center justify-center cursor-pointer">
                <span class="truncate">{{ __('checkout.cta_button') }}</span>
            </button>
        </div>
    </div>

    </form>
    </div>



    <!-- Upsell Modal -->
    <div id="upsell-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 @if (!$showUpsellModal) hidden @endif">
        <div class="bg-[#1F1F1F] rounded-xl max-w-md w-full mx-4">
            <div class="p-6">
                <button id="close-upsell" wire:click.prevent="rejectUpsell"
                    class="absolute top-3 right-3 text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="text-center mb-4">
                    <div class="bg-[#E50914] rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ __('payment.save_more') }}</h3>
                    <p class="text-gray-300 mt-2">{{ __('payment.semi_annual_free_months') }}</p>
                </div>

                <div class="bg-[#2D2D2D] rounded-lg p-4 mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-300">{{ __('payment.monthly') }} {{ __('payment.current') }}</span>
                        <span class="text-white font-medium" id="upsell-monthly">
                            {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                            {{ $modalData['actual_month_value'] ?? 00 }} {{ __('payment.per_month') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white font-medium">
                            {{ __('payment.semi-annual') }}
                        </span>
                        <span class="text-[#E50914] font-bold" id="upsell-annual">
                            {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                            {{ $modalData['offer_month_value'] ?? 00 }}
                            {{ __('payment.per_month') }}
                        </span>
                    </div>
                    <div class="mt-2 text-green-500 text-sm text-right" id="upsell-savings">
                        {{ __('payment.savings') }}
                        {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                        {{ $modalData['offer_total_discount'] ?? 00 }}
                        {{ __('payment.semi-annual') }}
                    </div>
                    <div class="mt-2 text-sm text-right" id="upsell-savings">
                        {{ __('payment.total_to_pay') }}
                        {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                        {{ $modalData['offer_total_value'] ?? 00 }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button id="upsell-reject" wire:click.prevent="rejectUpsell"
                        class="py-3 text-white font-medium rounded-lg border border-gray-600 hover:bg-[#2D2D2D] transition-colors">
                        {{ __('payment.keep_plan') }}
                    </button>
                    <button id="upsell-accept" wire:click.prevent="acceptUpsell"
                        class="py-3 bg-[#E50914] hover:bg-[#B8070F] text-white font-bold rounded-lg transition-colors">
                        {{ __('payment.want_to_save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing Modal -->
    <div id="processing-modal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 @if (!$showProcessingModal) hidden @endif">
        <div class="bg-[#1F1F1F] rounded-xl p-8 max-w-md w-full mx-4 text-center">
            <div class="mb-4">
                <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-[#E50914] border-r-2 border-b-2 border-transparent">
                </div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">{{ __('payment.processing_payment') }}</h3>
            <p class="text-gray-300">{{ __('payment.please_wait') }}</p>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">{{ __('payment.processing_payment') }}</h3>
        <p class="text-gray-300">{{ __('payment.please_wait') }}</p>
    </div>

    <!-- Error Modal -->
    <div id="error-modal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 @if (!$showErrorModal) hidden @endif">
        <div class="bg-[#1F1F1F] rounded-xl p-8 max-w-md w-full mx-4 text-center">
            <h3 class="text-xl font-bold text-white mb-2">{{ __('payment.processing_error') }}</h3>
            <p class="text-gray-300">{{ __('payment.error') }}</p>
                <button id="close-error" wire:click.prevent="closeModal"
                    class="bg-[#E50914] hover:bg-[#B8070F] text-white py-2 px-6 text-base font-semibold rounded-full shadow-lg w-auto min-w-[180px] max-w-xs mx-auto transition-all flex items-center justify-center cursor-pointer">
                    Close
                </button>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="success-modal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 @if (!$showSuccessModal) hidden @endif">
    <div class="bg-[#1F1F1F] rounded-xl p-8 max-w-md w-full mx-4 text-center">
        <h3 class="text-xl font-bold text-white mb-2">{{ __('payment.success') }}</h3>
            <button id="close-error" wire:click.prevent="closeModal"
                class="bg-[#42b70a] hover:bg-[#2a7904] text-white py-2 px-6 text-base font-semibold rounded-full shadow-lg w-auto min-w-[180px] max-w-xs mx-auto transition-all flex items-center justify-center cursor-pointer">
                Close
            </button>
    </div>

    <!-- Downsell Modal -->
    <div id="downsell-modal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 @if (!$showDownsellModal) hidden @endif">
        <div class="bg-[#1F1F1F] rounded-xl max-w-md w-full mx-4">
            <div class="p-6">
                <button id="close-downsell" wire:click.prevent="rejectDownsell"
                    class="absolute top-3 right-3 text-gray-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="text-center mb-4">
                    <div class="bg-[#E50914] rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-4">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.519 4.674c.3.921-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.519-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white">{{ __('payment.special_offer') }}</h3>
                    <p class="text-gray-300 mt-2">{{ __('payment.try_quarterly') }}</p>
                </div>
                <h3 class="text-2xl font-bold text-white">{{ __('payment.special_offer') }}</h3>
                <p class="text-gray-300 mt-2">{{ __('payment.try_quarterly') }}</p>
            </div>

            <div class="bg-[#2D2D2D] rounded-lg p-4 mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-300">{{ __('payment.monthly') }} {{ __('payment.current') }}</span>
                    <span class="text-white font-medium" id="downsell-monthly">
                        {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                        {{ $modalData['actual_month_value'] ?? 00 }} {{ __('payment.per_month') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-white font-medium">{{ __('payment.quarterly') }}
                    </span>
                    <span class="text-[#E50914] font-bold" id="downsell-quarterly">
                        {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                        {{ $modalData['offer_month_value'] ?? 00 }}
                        {{ __('payment.per_month') }}

                    </span>
                </div>
                <div class="mt-2 text-green-500 text-sm text-right" id="downsell-savings">
                    {{ __('payment.savings') }}
                    {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                    {{ $modalData['offer_total_discount'] ?? 00 }}
                    {{ __('payment.quarterly') }}
                </div>
                <div class="mt-2 text-sm text-right" id="upsell-savings">
                    {{ __('payment.total_to_pay') }}
                    {{ $currencies[$selectedCurrency]['symbol'] ?? 'R$' }}
                    {{ $modalData['offer_total_value'] ?? 00 }}
                </div>

            <div class="grid grid-cols-2 gap-3">
                <button id="downsell-reject" wire:click.prevent="rejectDownsell"
                    class="py-3 text-white font-medium rounded-lg border border-gray-600 hover:bg-[#2D2D2D] transition-colors">
                    {{ __('payment.no_thanks') }}
                </button>
                <button id="downsell-accept" wire:click.prevent="acceptDownsell"
                    class="py-3 bg-[#E50914] hover:bg-[#B8070F] text-white font-bold rounded-lg transition-colors">
                    {{ __('payment.want_offer') }}
                </button>
            </div>
        </div>
    </div>

<!-- Personalização Modal -->
<div class="fixed inset-0 bg-black bg-opacity-80 flex flex-col justify-center items-center text-white z-50 @if (!$showLodingModal) hidden @endif">
    <svg class="animate-spin h-10 w-10 text-red-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
            stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
    </svg>
    <p class="text-lg">{{ __('payment.customizing') }}</p>
    <p class="text-sm mt-2 text-gray-400">{{ __('payment.optimizing') }}</p>
</div>
<!-- Stripe JS -->
@push('scripts')
@if($gateway === 'stripe')
<script src="https://js.stripe.com/v3/"></script>
<script>
    let stripeCard = null;
    const stripe = Stripe("{{ config('services.stripe.api_public_key') }}");

    function initializeStripe() {
        if (stripeCard) {
            stripeCard.destroy();
            stripeCard = null;
        }

        if (!document.getElementById('card-element')) {
            return;
        }

        const elements = stripe.elements();
        const style = {
            base: {
                color: '#ffffffff',
                fontFamily: '"Urbanist", sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#868686ff'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };
        stripeCard = elements.create('card', {
            style: style,
            hidePostalCode: true
        });

        stripeCard.mount('#card-element');

        stripeCard.on('change', async (event) => {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }

            if (event.complete) {
                const { error, paymentMethod } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: stripeCard,
                    billing_details: {
                        name: document.querySelector('input[name="card_name"]').value,
                        email: document.querySelector('input[name="email"]').value,
                        phone: document.querySelector('input[name="phone"]').value,
                    },
                });

                if (error) {
                    displayError.textContent = error.message;
                } else {
                    @this.set('paymentMethodId', paymentMethod.id);
                }
            }
        });
    }

    document.addEventListener('livewire:init', () => {
        initializeStripe();

        Livewire.hook('message.processed', (message, component) => {
            initializeStripe();
        });

        Livewire.on('checkout-success', (event) => {
            const purchaseData = event.purchaseData;
            if (typeof fbq === 'function') {
                fbq('track', 'Purchase', {
                    value: purchaseData.value,
                    currency: purchaseData.currency,
                    content_ids: purchaseData.content_ids,
                    content_type: purchaseData.content_type,
                    transaction_id: purchaseData.transaction_id
                });
            }
        });

        Livewire.on('validation:failed', () => {
            const paymentSection = document.getElementById('payment-method-section');
            if (paymentSection) {
                paymentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    window.addEventListener('redirectToExternal', event => {
        window.location.href = event.detail.url;
    });
</script>
@endif
@endpush
</div>
