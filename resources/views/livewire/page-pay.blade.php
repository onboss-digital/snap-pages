@section('head')
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/ico" href="{{ asset('imgs/mini_logo.png') }}" />
    <script src="https://cdn.tailwindcss.com"></script>
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
            transform: translateY(100%);
            transition: transform 0.3s ease-in-out;
        }

        .sticky-summary.show {
            transform: translateY(0);
        }
    </style>
@endsection

@section('scripts')
    @vite('resources/js/pages/pay.js')
@endsection


<div>
    <div class="container mx-auto px-4 py-8 max-w-4xl">

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

            <div class="mt-6 w-full h-32 md:h-48 rounded-xl overflow-hidden relative bg-gray-900">
                <div class="absolute inset-0 bg-gradient-to-t from-[#121212] via-transparent to-transparent">
                    <img class="img logo" src="{{ asset('imgs/banner brasil.jpg') }}" alt="streamit">

                </div>
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-white mt-6 text-center md:text-left">
                {{ __('payment.start_subscription') }}</h1>
            <p class="text-lg text-gray-300 mt-2 text-center md:text-left">{{ __('payment.unlock_access') }}</p>
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
                        <h2 class="text-xl font-semibold text-white mb-4">{{ __('payment.premium_benefits') }}</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="benefits-container">

                            @foreach ($benefits as $benefit)
                                <div class="flex items-start space-x-3">
                                    <div class="p-2 bg-[#E50914] rounded-lg">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-white">{{ $benefit['title'] }}</h3>
                                        <p class="text-sm text-gray-400">{{ $benefit['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-[#1F1F1F] rounded-xl p-6 mb-6">
                        <h2 class="text-xl font-semibold text-white mb-4">{{ __('payment.payment_method') }}</h2>

                        <div class="grid grid-cols-1 gap-3">
                            <div class="relative">
                                <input type="radio" id="payment-card" name="payment_method" value="credit_card"
                                    class="peer sr-only" checked />
                                <label for="payment-card"
                                    class="flex flex-col items-center justify-center p-4 rounded-lg border border-gray-700 bg-[#2D2D2D] cursor-pointer transition-all hover:bg-gray-800  peer-checked:bg-[#2D2D2D] h-24">
                                    <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span class="text-sm font-medium text-white">{{ __('payment.card') }}</span>
                                </label>
                            </div>

                        </div>

                        <!-- Card payment form - shown conditionally -->
                        <div id="card-payment-form" class="mt-6">
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.card_number') }}</label>
                                    <input name="card_number" type="text" id="card-number"
                                        x-mask="9999 9999 9999 9999" placeholder="0000 0000 0000 0000"
                                        wire:model.defer="cardNumber"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    @error('cardNumber')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
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
                                    @error('email')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.phone') }}</label>
                                    <input name="phone" type="text" id="phone"
                                        placeholder="(00) 00000-0000" x-mask="(99) 99999-9999"
                                        wire:model.defer="phone"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    @error('phone')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- CPF Field - Only visible for Brazilian currency -->
                                <div x-data="{}" x-show="$wire.selectedCurrency === 'BRL'">
                                    <label
                                        class="block text-sm font-medium text-gray-300 mb-1">{{ __('payment.cpf') }}</label>
                                    <input name="cpf" type="text" id="cpf" placeholder="000.000.000-00"
                                        x-mask="999.999.999-99" wire:model.defer="cpf"
                                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all" />
                                    @error('cpf')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Bump -->
                    <div class="bg-[#1F1F1F] rounded-xl p-5 mb-6 border border-gray-700">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input name="order_bump" id="order-bump" type="checkbox"
                                    class="w-5 h-5 text-[#E50914] bg-[#2D2D2D] border-gray-600 rounded focus:ring-[#E50914] focus:ring-opacity-25 focus:ring-2 focus:border-[#E50914] cursor-pointer" />
                            </div>
                            <label for="order-bump" class="ml-3 cursor-pointer">
                                <div class="text-white text-base font-semibold flex items-center">
                                    <svg class="h-5 w-5 text-[#E50914] mr-1" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ __('payment.exclusive_access') }}
                                </div>
                                <p class="text-gray-400 text-sm mt-1">{{ __('payment.participate_live') }}</p>
                                <p class="text-[#E50914] font-medium mt-2">+<span
                                        id="bump-price">{{ $currencies[$selectedCurrency]['symbol'] }}
                                        {{ $bump['price'] ?? '00' }}</span>{{ __('payment.per_month') }}
                                </p>
                            </label>
                        </div>
                    </div>

                    <!-- Order Bump Unlock Animation -->
                    <div id="order-bump-unlock"
                        class="bg-[#1F1F1F] rounded-xl p-5 mb-6 border border-[#E50914] @if (!$bumpActive) hidden @endif animate-fade">
                        <div class="flex items-center justify-center text-center">
                            <div>
                                <div class="text-2xl mb-2">✨</div>
                                <p class="text-white font-semibold">{{ __('payment.bonus_unlocked') }}</p>
                                <p class="text-gray-400 text-sm mt-1">{{ __('payment.access_exclusive') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonials -->
                    <div class="bg-[#1F1F1F] rounded-xl p-6 mb-6">
                        <h2 class="text-xl font-semibold text-white mb-4">{{ __('payment.subscribers_say') }}</h2>

                        <div class="space-y-4">
                            <div class="bg-[#2D2D2D] p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="flex text-yellow-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-400">{{ __('payment.days_ago') }}</span>
                                </div>
                                <p class="text-white text-sm">{{ __('payment.testimonial_1') }}</p>
                                <div class="mt-3 text-sm font-medium text-gray-400">
                                    {{ __('payment.subscriber_1') }}
                                </div>
                            </div>

                            <div class="bg-[#2D2D2D] p-4 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <div class="flex text-yellow-400">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                            </path>
                                        </svg>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-400">{{ __('payment.days_ago') }}</span>
                                </div>
                                <p class="text-white text-sm">{{ __('payment.testimonial_2') }}</p>
                                <div class="mt-3 text-sm font-medium text-gray-400">
                                    {{ __('payment.subscriber_2') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="md:col-span-1">
                    <div class="bg-[#1F1F1F] rounded-xl p-6 sticky top-6" wire:poll.1s="decrementTimer"
                        wire:poll.15000ms="decrementSpotsLeft" wire:poll.8000ms="updateLiveActivity">
                        <h2 class="text-xl font-semibold text-white mb-4">{{ __('payment.order_summary') }}</h2>

                        <!-- Timer -->
                        <div class="bg-[#2D2D2D] rounded-lg p-3 mb-4 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#E50914] mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-white">{{ __('payment.offer_expires') }} <span id="countdown-timer"
                                    class="font-bold">{{ sprintf('%02d:%02d', $countdownMinutes, $countdownSeconds) }}</span></span>
                        </div>

                        <!-- Plan selection -->
                        <div class="mb-6">
                            <label
                                class="block text-sm font-medium text-gray-300 mb-2">{{ __('payment.select_plan') }}</label>

                            <div class="relative">
                                <select id="plan-selector" name="plan"
                                    class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 appearance-none pr-10 focus:outline-none focus:ring-1 focus:ring-[#E50914] transition-all"
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
                        <div class="border-y border-gray-700 py-5 my-4 space-y-2">
                            <!-- Coupon area -->
                            <div>
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
                                    {{ __('payment.coupon_success') }}</div>
                            </div>
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
                            <span class="font-medium">VAGAS RESTANTES: <strong><span
                                        id="spots-left">{{ $spotsLeft }}</span> (do lote atual)</strong></span>
                        </div>

                        <!-- Live Activity Indicator -->
                        <div class="bg-[#2D2D2D] rounded-lg p-3 mb-6 text-center">
                            <span class="text-gray-400">Cerca de <strong id="activityCounter"
                                    class="text-white">{{ $activityCount }}</strong> pessoas estão
                                finalizando...</span>
                        </div>

                        <!-- Verificação de Ambiente Seguro -->
                        <div id="seguranca"
                            class="w-full bg-gray-800 p-4 rounded-lg flex items-center gap-3 text-sm text-gray-300 animate-pulse mb-4"
                            wire:show="showSecure" x-transition.duration.500ms>
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2.003 5.884L10 2l7.997 3.884v4.632c0 5.522-3.936 10.74-7.997 11.484-4.061-.744-7.997-5.962-7.997-11.484V5.884z" />
                            </svg>
                            {{ __('payment.checking_secure') }}
                        </div>

                        <button id="checkout-button" type="button" wire:click.prevent="startCheckout"
                            class="w-full bg-[#E50914] hover:bg-[#B8070F] text-white py-3 text-lg font-bold rounded-xl transition-all">
                            {{ __('payment.start_premium') }}
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
                <div class="container mx-auto">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-white font-medium" id="sticky-plan">{{ __('payment.monthly') }}</div>
                            <div class="text-[#E50914]" id="sticky-price">R$58,99/{{ __('payment.per_month') }}</div>
                        </div>
                        <button type="button" id="sticky-checkout-button"
                            class="bg-[#E50914] hover:bg-[#B8070F] text-white py-2 px-4 text-sm font-bold rounded-lg transition-all">
                            {{ __('payment.start_premium') }}
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>



    <!-- Upsell Modal -->
    <div id="upsell-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        wire:show="showUpsellModal" x-transition.duration.500ms>
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
    </div>

    <!-- Processing Modal -->
    <div id="processing-modal"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 @if (!$showProcessingModal) hidden @endif">
        <div class="bg-[#1F1F1F] rounded-xl p-8 max-w-md w-full mx-4 text-center">
            <div class="mb-4">
                <div
                    class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-[#E50914] border-r-2 border-b-2 border-transparent">
                </div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">{{ __('payment.processing_payment') }}</h3>
            <p class="text-gray-300">{{ __('payment.please_wait') }}</p>
        </div>
    </div>

    <!-- Personalização Modal -->
    <div id="personalizacao"
        class="fixed inset-0 bg-black bg-opacity-80 flex flex-col justify-center items-center text-white z-50 "
        wire:show="showLodingModal" x-transition.duration.500ms>
        <svg class="animate-spin h-10 w-10 text-red-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
        <p class="text-lg">{{ __('payment.customizing') }}</p>
        <p class="text-sm mt-2 text-gray-400">{{ __('payment.optimizing') }}</p>
    </div>
</div>
