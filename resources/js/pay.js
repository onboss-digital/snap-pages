// Currency conversion rates
const conversionRates = {
    BRL: 1.0,
    USD: 0.17,
    EUR: 0.16,
    GBP: 0.13
};

// Currency symbols
const currencySymbols = {
    BRL: "R$",
    USD: "$",
    EUR: "€",
    GBP: "£"
};

// Base prices in BRL
const basePrices = {
    monthly: 58.99,
    quarterly: 53.09, // 10% off
    annual: 44.24,    // 25% off
    bump: 9.99
};

// Benefits data
const benefits = [
    {
        title: "Vídeos premium",
        description: "Acesso a todo nosso conteúdo sem restrições"
    },
    {
        title: "Conteúdos diários",
        description: "Novas atualizações todos os dias"
    },
    {
        title: "Sem anúncios",
        description: "Experiência limpa e sem interrupções"
    },
    {
        title: "Personalização",
        description: "Configure sua conta como preferir"
    },
    {
        title: "Atualizações semanais",
        description: "Novas funcionalidades toda semana"
    },
    {
        title: "Votação e sugestões",
        description: "Ajude a moldar o futuro da plataforma"
    }
];

// Current state
let state = {
    currency: localStorage.getItem('currency') || 'BRL',
    plan: localStorage.getItem('plan') || 'monthly',
    paymentMethod: 'card',
    orderBumpEnabled: false,
    progressStep: 1,
    countdownMinutes: 14,
    countdownSeconds: 22,
    spotsLeft: 12,
    couponApplied: false,
    couponDiscount: 0,
    originalPrice: 89.90
};

// DOM Elements
const currencySelector = document.getElementById('currency-selector');
const planSelector = document.getElementById('plan-selector');
const benefitsContainer = document.getElementById('benefits-container');
const orderBumpCheckbox = document.getElementById('order-bump');
const orderBumpUnlock = document.getElementById('order-bump-unlock');
const bumpPriceEl = document.getElementById('bump-price');
const bumpPriceRowEl = document.getElementById('bump-price-row');
const bumpPriceSummaryEl = document.getElementById('bump-price-summary');
const planPriceEl = document.getElementById('plan-price');
const totalPriceEl = document.getElementById('total-price');
const currentPriceEl = document.getElementById('current-price');
const progressBar = document.getElementById('progressBar');
const countdownTimer = document.getElementById('countdown-timer');
const spotsLeftEl = document.getElementById('spots-left');
const checkoutButton = document.getElementById('checkout-button');
const stickyCheckoutButton = document.getElementById('sticky-checkout-button');
const stickyPlan = document.getElementById('sticky-plan');
const stickyPrice = document.getElementById('sticky-price');
const stickySummary = document.getElementById('sticky-summary');
const supportBalloon = document.getElementById('support-balloon');
const closeSupportButton = document.getElementById('close-support');
const cardPaymentForm = document.getElementById('card-payment-form');
const cardNumber = document.getElementById('card-number');
const cardExpiry = document.getElementById('card-expiry');
const cardCvv = document.getElementById('card-cvv');

// Novos elementos
const personalizacaoModal = document.getElementById('personalizacao');
const segurancaVerificacao = document.getElementById('seguranca');
const chatButton = document.getElementById('chat-button');
const chatPanel = document.getElementById('chat');
const closeChat = document.getElementById('close-chat');
const activityCounter = document.getElementById('activityCounter');
const couponInput = document.getElementById('coupon-input');
const applyButton = document.getElementById('apply-coupon');
const couponMessage = document.getElementById('coupon-message');
const finalPrice = document.getElementById('final-price');
const inputFinalPrice = document.getElementById('input-final-price');

const discountAmount = document.getElementById('discount-amount');

// Upsell and Downsell Modals
const upsellModal = document.getElementById('upsell-modal');
const downsellModal = document.getElementById('downsell-modal');
const processingModal = document.getElementById('processing-modal');
const closeUpsellButton = document.getElementById('close-upsell');
const closeDownsellButton = document.getElementById('close-downsell');
const upsellAcceptButton = document.getElementById('upsell-accept');
const upsellRejectButton = document.getElementById('upsell-reject');
const downsellAcceptButton = document.getElementById('downsell-accept');
const downsellRejectButton = document.getElementById('downsell-reject');
const upsellMonthlyEl = document.getElementById('upsell-monthly');
const upsellAnnualEl = document.getElementById('upsell-annual');
const upsellSavingsEl = document.getElementById('upsell-savings');
const downsellMonthlyEl = document.getElementById('downsell-monthly');
const downsellQuarterlyEl = document.getElementById('downsell-quarterly');
const downsellSavingsEl = document.getElementById('downsell-savings');

// Initialize payment method radios
const paymentRadios = document.querySelectorAll('input[name="payment-method"]');

// Convert price to selected currency
function convertPrice(priceInBRL, currency) {
    return priceInBRL * conversionRates[currency];
}

// Format price with correct currency symbol
function formatPrice(price, currency) {
    return `${currencySymbols[currency]}${price.toFixed(2)}`;
}

// Update all prices based on current currency and plan
function updatePrices() {
    const planPrice = convertPrice(basePrices[state.plan], state.currency);
    const bumpPrice = convertPrice(basePrices.bump, state.currency);
    const originalPrice = convertPrice(state.originalPrice, state.currency);

    // Calculate total
    let total = planPrice;
    if (state.orderBumpEnabled) {
        total += bumpPrice;
    }

    // Apply coupon discount if it's active
    if (state.couponApplied) {
        total = total * (1 - state.couponDiscount);
    }

    // Calculate discount amount (original price - final price)
    const discountVal = originalPrice - total;

    // Update displayed prices
    planPriceEl.textContent = formatPrice(planPrice, state.currency);
    bumpPriceEl.textContent = formatPrice(bumpPrice, state.currency);
    bumpPriceSummaryEl.textContent = formatPrice(bumpPrice, state.currency);
    totalPriceEl.textContent = formatPrice(total, state.currency);
    currentPriceEl.textContent = formatPrice(planPrice, state.currency) + '/mês';

    // Update resumo final prices
    finalPrice.textContent = formatPrice(total, state.currency);
    discountAmount.textContent = `-${formatPrice(discountVal, state.currency)}`;

    // Update sticky summary
    stickyPlan.textContent = `Plano ${state.plan === 'monthly' ? 'Mensal' : state.plan === 'quarterly' ? 'Trimestral' : 'Anual'}`;
    stickyPrice.textContent = formatPrice(planPrice, state.currency) + '/mês';

    // Update upsell modal prices
    const monthlyPrice = convertPrice(basePrices.monthly, state.currency);
    const annualPrice = convertPrice(basePrices.annual, state.currency);
    const monthlySavings = convertPrice((basePrices.monthly - basePrices.annual) * 12, state.currency);

    upsellMonthlyEl.textContent = formatPrice(monthlyPrice, state.currency) + '/mês';
    upsellAnnualEl.textContent = formatPrice(annualPrice, state.currency) + '/mês';
    upsellSavingsEl.textContent = `Economia de ${formatPrice(monthlySavings, state.currency)}/ano`;

    // Update downsell modal prices
    const quarterlyPrice = convertPrice(basePrices.quarterly, state.currency);
    const quarterlySavings = convertPrice((basePrices.monthly - basePrices.quarterly) * 3, state.currency);

    downsellMonthlyEl.textContent = formatPrice(monthlyPrice, state.currency) + '/mês';
    downsellQuarterlyEl.textContent = formatPrice(quarterlyPrice, state.currency) + '/mês';
    downsellSavingsEl.textContent = `Economia de ${formatPrice(quarterlySavings, state.currency)}/trimestre`;

    console.log('Total:', total);
    if (inputFinalPrice) {
        inputFinalPrice.value = total;
    }

}

// Initialize benefits
function initBenefits() {
    benefitsContainer.innerHTML = '';

    benefits.forEach((benefit) => {
        const benefitEl = document.createElement('div');
        benefitEl.className = 'flex items-start space-x-3';
        benefitEl.innerHTML = `
      <div class="p-2 bg-[#E50914] rounded-lg">
        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <div>
        <h3 class="font-medium text-white">${benefit.title}</h3>
        <p class="text-sm text-gray-400">${benefit.description}</p>
      </div>
    `;

        benefitsContainer.appendChild(benefitEl);
    });
}

// Update progress bar
function updateProgress(step) {
    state.progressStep = step;
    const progress = (step / 4) * 100;
    progressBar.style.width = `${progress}%`;
}

// Start countdown timer
function startCountdown() {
    const updateCountdown = () => {
        if (state.countdownSeconds === 0) {
            if (state.countdownMinutes === 0) {
                clearInterval(countdownInterval);
                countdownTimer.textContent = "Expirado";
                return;
            }
            state.countdownMinutes--;
            state.countdownSeconds = 59;
        } else {
            state.countdownSeconds--;
        }

        const minutes = state.countdownMinutes.toString().padStart(2, '0');
        const seconds = state.countdownSeconds.toString().padStart(2, '0');
        countdownTimer.textContent = `${minutes}:${seconds}`;
    };

    const countdownInterval = setInterval(updateCountdown, 1000);
}

// Detect currency by geolocation
function detectCurrencyByGeolocation() {
    fetch('https://ipapi.co/json')
        .then(res => res.json())
        .then(data => {
            if (data.currency === 'USD' || data.currency === 'EUR' || data.currency === 'GBP') {
                state.currency = data.currency;
                currencySelector.value = state.currency;
                updatePrices();
                localStorage.setItem('currency', state.currency);
            }
        })
        .catch(err => console.error('Failed to detect location:', err));
}

// Show/hide sticky summary based on scroll position
function handleScroll() {
    if (window.innerWidth < 768) { // Only on mobile
        if (window.scrollY > 300) {
            stickySummary.classList.add('show');
        } else {
            stickySummary.classList.remove('show');
        }
    }
}

function sendPayment() {
    //payment-form
    document.getElementById('payment-form').submit();




}


// Card input masks
function setupCardMasks() {
    // Credit card number mask: 0000 0000 0000 0000
    cardNumber.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 16) value = value.slice(0, 16);

        // Add spaces
        let maskedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) maskedValue += ' ';
            maskedValue += value[i];
        }

        e.target.value = maskedValue;
    });

    // Expiry date mask: MM/YY
    cardExpiry.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4) value = value.slice(0, 4);

        // Add slash
        if (value.length > 2) {
            value = value.slice(0, 2) + '/' + value.slice(2);
        }

        e.target.value = value;
    });

    // CVV mask: 000
    cardCvv.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 3) value = value.slice(0, 3);
        e.target.value = value;
    });
}

// Handle checkout process
function handleCheckout() {
    // Show processing modal
    processingModal.classList.remove('hidden');

    // Simulate processing
    setTimeout(() => {
        processingModal.classList.add('hidden');

        if (state.plan === 'monthly') {
            upsellModal.classList.remove('hidden');
        } else {
            sendPayment();
        }
    }, 2000);
}

// Função para mostrar personalização
function showPersonalizacao() {
    personalizacaoModal.classList.remove('hidden');

    setTimeout(() => {
        personalizacaoModal.classList.add('hidden');
    }, 3000);
}

// Função para mostrar verificação de segurança
function showSeguranca() {
    segurancaVerificacao.classList.remove('hidden');

    setTimeout(() => {
        segurancaVerificacao.classList.add('hidden');
    }, 4000);
}

// Event Listeners
function setupEventListeners() {
    // Currency selector
    currencySelector.addEventListener('change', function () {
        state.currency = this.value;
        updatePrices();
        localStorage.setItem('currency', state.currency);

        // Mostrar personalização ao trocar moeda
        showPersonalizacao();
    });

    // Plan selector
    planSelector.addEventListener('change', function () {
        state.plan = this.value;
        updatePrices();
        localStorage.setItem('plan', state.plan);
        updateProgress(state.progressStep < 2 ? 2 : state.progressStep);

        // Mostrar personalização ao trocar plano
        showPersonalizacao();
    });

    // Order bump checkbox
    orderBumpCheckbox.addEventListener('change', function () {
        state.orderBumpEnabled = this.checked;
        updatePrices();

        if (this.checked) {
            bumpPriceRowEl.classList.remove('hidden');
            orderBumpUnlock.classList.remove('hidden');
            updateProgress(state.progressStep < 3 ? 3 : state.progressStep);

            // Reduce spots left for urgency
            state.spotsLeft--;
            spotsLeftEl.textContent = state.spotsLeft;
        } else {
            bumpPriceRowEl.classList.add('hidden');
            orderBumpUnlock.classList.add('hidden');
        }
    });

    // Payment method radios
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            state.paymentMethod = this.value;

            // Show/hide card form
            if (state.paymentMethod === 'card') {
                cardPaymentForm.classList.add('show-form');
                updateProgress(state.progressStep < 3 ? 3 : state.progressStep);
            } else {
                cardPaymentForm.classList.remove('show-form');
            }
        });
    });

    // Support ballon já não é mais usado, apenas o closeSupportButton é mantido para evitar erros
    if (closeSupportButton) {
        closeSupportButton.addEventListener('click', function () {
            supportBalloon.classList.add('hidden');
        });
    }

    // Chat rápido
    chatButton.addEventListener('click', function () {
        chatPanel.classList.toggle('hidden');
    });

    closeChat.addEventListener('click', function () {
        chatPanel.classList.add('hidden');
    });

    // Checkout buttons
    checkoutButton.addEventListener('click', function () {
        updateProgress(4);

        // Mostrar verificação de segurança antes de processar
        showSeguranca();

        // Depois de verificar, mostrar processamento
        setTimeout(() => {
            handleCheckout();
        }, 3000);
    });

    stickyCheckoutButton.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => {
            updateProgress(4);

            // Mostrar verificação de segurança antes de processar
            showSeguranca();

            // Depois de verificar, mostrar processamento
            setTimeout(() => {
                handleCheckout();
            }, 3000);
        }, 500);
    });

    // Upsell modal
    closeUpsellButton.addEventListener('click', function () {
        upsellModal.classList.add('hidden');
    });

    upsellAcceptButton.addEventListener('click', function () {
        upsellModal.classList.add('hidden');
        state.plan = 'annual';
        planSelector.value = state.plan;
        updatePrices();
        localStorage.setItem('plan', state.plan);
        sendPayment();
    });

    upsellRejectButton.addEventListener('click', function () {
        upsellModal.classList.add('hidden');
        downsellModal.classList.remove('hidden');
    });

    // Downsell modal
    closeDownsellButton.addEventListener('click', function () {
        downsellModal.classList.add('hidden');
    });

    downsellAcceptButton.addEventListener('click', function () {
        downsellModal.classList.add('hidden');
        state.plan = 'quarterly';
        planSelector.value = state.plan;
        updatePrices();
        localStorage.setItem('plan', state.plan);

        sendPayment();

        window.addEventListener('scroll', handleScroll);

    });

    downsellRejectButton.addEventListener('click', function () {
        downsellModal.classList.add('hidden');
        sendPayment();
    });

    // Scroll event for sticky summary
    window.addEventListener('scroll', handleScroll);
}

// Update the live activity counter
function startLiveActivityCounter() {
    setInterval(function () {
        let count = Math.floor(Math.random() * 50) + 1;
        activityCounter.textContent = count;
    }, 5000);
}

// Apply coupon code
function applyCoupon() {
    const couponCode = couponInput.value.trim().toUpperCase();

    if (couponCode === 'DESCONTO20') {
        state.couponApplied = true;
        state.couponDiscount = 0.20; // 20% discount
        couponMessage.textContent = 'Cupom de 20% aplicado com sucesso!';
        couponMessage.classList.remove('hidden');
        couponMessage.classList.add('text-green-400');
        couponInput.disabled = true;
        applyButton.disabled = true;
        applyButton.classList.add('opacity-50');
        updatePrices();
        updateProgress(state.progressStep < 3 ? 3 : state.progressStep);
    } else if (couponCode === 'PROMO10') {
        state.couponApplied = true;
        state.couponDiscount = 0.10; // 10% discount
        couponMessage.textContent = 'Cupom de 10% aplicado com sucesso!';
        couponMessage.classList.remove('hidden');
        couponMessage.classList.add('text-green-400');
        couponInput.disabled = true;
        applyButton.disabled = true;
        applyButton.classList.add('opacity-50');
        updatePrices();
        updateProgress(state.progressStep < 3 ? 3 : state.progressStep);
    } else {
        couponMessage.textContent = 'Cupom inválido, tente novamente.';
        couponMessage.classList.remove('hidden');
        couponMessage.classList.remove('text-green-400');
        couponMessage.classList.add('text-red-400');
    }
}

// Initialize
function init() {

    // Set initial values from state
    currencySelector.value = state.currency;
    planSelector.value = state.plan;

    // Initial payment method setup
    document.getElementById('payment-card').checked = true;
    cardPaymentForm.classList.add('show-form');

    // Initialize components
    initBenefits();
    updatePrices();
    updateProgress(1);
    setupCardMasks();
    setupEventListeners();
    startCountdown();
    startLiveActivityCounter();

    // Set the spots left
    spotsLeftEl.textContent = state.spotsLeft;

    // Set up coupon event listener
    applyButton.addEventListener('click', applyCoupon);

    // Try to detect currency by location
    detectCurrencyByGeolocation();
}

// Start everything when DOM is ready
document.addEventListener('DOMContentLoaded', init);