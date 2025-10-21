@props(['pixResult', 'pixStatus', 'expiresAt'])

@php
$statusBadges = [
    'PENDING' => ['text' => 'Aguardando Pagamento', 'class' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/50'],
    'PAID' => ['text' => 'Pagamento Confirmado! 🎉', 'class' => 'bg-green-500/20 text-green-400 border-green-500/50'],
    'EXPIRED' => ['text' => 'Expirado', 'class' => 'bg-red-500/20 text-red-400 border-red-500/50'],
    'FAILED' => ['text' => 'Falhou', 'class' => 'bg-red-500/20 text-red-400 border-red-500/50'],
];

$currentStatus = $statusBadges[$pixStatus] ?? $statusBadges['PENDING'];
@endphp

<div class="space-y-4">

    @if($pixStatus === 'PAID')
        <!-- Tela de Sucesso -->
        <div class="bg-green-500/10 border border-green-500/50 rounded-lg p-6 text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h4 class="text-2xl font-bold text-green-400 mb-2">Pagamento Confirmado!</h4>
            <p class="text-gray-300 text-sm">Você será redirecionado em instantes...</p>
            <div class="mt-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-green-500"></div>
            </div>
        </div>
        
    @elseif(in_array($pixStatus, ['EXPIRED', 'FAILED']))
        <!-- Tela de Erro/Expiração -->
        <div class="bg-red-500/10 border border-red-500/50 rounded-lg p-6 text-center">
            <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h4 class="text-2xl font-bold text-red-500 mb-2">
                {{ $pixStatus === 'EXPIRED' ? 'Pagamento Expirado' : 'Pagamento Falhou' }}
            </h4>
            <p class="text-gray-300 text-sm mb-4">Por favor, tente novamente</p>
            <button 
                wire:click="$set('pixResult', null)"
                class="px-6 py-3 bg-[#E50914] text-white rounded-lg font-semibold hover:bg-red-700 transition-all shadow-lg">
                Gerar Novo PIX
            </button>
        </div>
        
    @else
        <!-- Tela de Pagamento Pendente -->
        
        <!-- Badge de Status -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2">
                <svg class="w-6 h-6 text-green-500" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M242.4 292.5C247.8 287.1 257.1 287.1 262.5 292.5L339.5 369.5C353.7 383.7 372.6 391.5 392.6 391.5H407.7L310.6 488.6C280.3 518.1 231.1 518.1 200.8 488.6L103.3 391.5H112.6C132.6 391.5 151.5 383.7 165.7 369.5L242.4 292.5zM262.5 218.9C257.1 224.3 247.8 224.3 242.4 218.9L165.7 142.1C151.5 127.9 132.6 120.1 112.6 120.1H103.3L200.7 22.76C231.1-7.586 280.3-7.586 310.6 22.76L407.7 120.1H392.6C372.6 120.1 353.7 127.9 339.5 142.1L262.5 218.9z"/>
                </svg>
                <span class="text-white font-semibold">Pagamento PIX</span>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $currentStatus['class'] }}">
                {{ $currentStatus['text'] }}
            </span>
        </div>

        <!-- QR Code -->
        <div class="bg-white rounded-lg p-6 text-center">
            @if(isset($pixResult['brCodeBase64']))
                <img 
                    src="{{ $pixResult['brCodeBase64'] }}"
                    alt="QR Code PIX" 
                    class="mx-auto w-48 h-48 object-contain"
                    id="pix-qr-image"
                />
            @else
                <div class="w-48 h-48 mx-auto flex items-center justify-center bg-gray-100 rounded-lg">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green-500 mb-3"></div>
                        <p class="text-gray-600 text-sm">Gerando QR Code...</p>
                    </div>
                </div>
            @endif
            <p class="text-gray-700 text-sm mt-4 font-semibold">
                📱 Escaneie com o app do seu banco
            </p>
        </div>

        <!-- Valor -->
        <div class="bg-[#2D2D2D] rounded-lg p-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-400 text-sm font-medium">Valor a pagar:</span>
                <span class="text-white font-bold text-xl text-green-400">
                    R$ {{ number_format(($pixResult['amount'] ?? 0) / 100, 2, ',', '.') }}
                </span>
            </div>
            @if(isset($pixResult['pix_id']))
            <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-700">
                <span class="text-gray-500 text-xs">ID da Transação:</span>
                <span class="text-gray-400 text-xs font-mono">{{ substr($pixResult['pix_id'], 0, 20) }}...</span>
            </div>
            @endif
        </div>

        <!-- Contador Regressivo -->
        @if(isset($expiresAt))
        <div class="bg-red-900/20 border border-red-500/50 rounded-lg p-4 text-center">
            <div class="flex items-center justify-center space-x-2 mb-2">
                <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-red-400 text-sm font-semibold">Tempo restante:</p>
            </div>
            <p class="text-white font-bold text-2xl tabular-nums" id="pix-countdown" data-expires-at="{{ $expiresAt }}">
                --:--
            </p>
        </div>
        @endif

        <!-- Código Copia e Cola -->
        <div class="bg-[#2D2D2D] rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-300 text-sm font-semibold">Código Pix Copia e Cola</span>
                <button 
                    type="button"
                    onclick="copyPixCode()"
                    class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg font-semibold text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Copiar</span>
                </button>
            </div>
            <div class="bg-black/50 rounded-lg p-3 border border-gray-700">
                <p class="text-gray-300 font-mono text-xs break-all leading-relaxed max-h-24 overflow-y-auto" id="pix-brcode">
                    {{ $pixResult['brCode'] ?? 'Carregando código...' }}
                </p>
            </div>
        </div>

        <!-- Botão "Já Paguei" -->
        <button 
            type="button"
            wire:click="checkPixStatus"
            class="w-full py-3 bg-green-600 hover:bg-green-500 text-white rounded-lg font-bold text-lg transition-all shadow-lg flex items-center justify-center space-x-2">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Já Paguei</span>
        </button>

        <!-- Informações de Segurança -->
        <div class="grid grid-cols-3 gap-3 text-center pt-2">
            <div class="flex flex-col items-center space-y-1">
                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-xs text-gray-400">100% Seguro</span>
            </div>
            <div class="flex flex-col items-center space-y-1">
                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="text-xs text-gray-400">Instantâneo</span>
            </div>
            <div class="flex flex-col items-center space-y-1">
                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span class="text-xs text-gray-400">Criptografado</span>
            </div>
        </div>
    @endif
</div>

<script>
// Função para copiar código PIX
function copyPixCode() {
    const brCode = document.getElementById('pix-brcode').innerText.trim();
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(brCode).then(() => {
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            
            button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg><span>Copiado!</span>';
            button.classList.remove('bg-green-600', 'hover:bg-green-500');
            button.classList.add('bg-green-700');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('bg-green-700');
                button.classList.add('bg-green-600', 'hover:bg-green-500');
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar:', err);
            alert('Erro ao copiar. Tente selecionar e copiar manualmente.');
        });
    } else {
        // Fallback para navegadores antigos
        const textArea = document.createElement('textarea');
        textArea.value = brCode;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg><span>Copiado!</span>';
            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        } catch (err) {
            alert('Erro ao copiar. Tente selecionar e copiar manualmente.');
        }
        
        document.body.removeChild(textArea);
    }
}

// Contador regressivo
function updatePixCountdown() {
    const countdownEl = document.getElementById('pix-countdown');
    if (!countdownEl) return;
    
    const expiresAt = countdownEl.getAttribute('data-expires-at');
    if (!expiresAt) return;
    
    const expiryTime = new Date(expiresAt).getTime();
    const now = new Date().getTime();
    const distance = expiryTime - now;
    
    if (distance < 0) {
        countdownEl.textContent = '00:00';
        countdownEl.classList.add('text-red-500');
        return;
    }
    
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Inicializar contador
if (document.getElementById('pix-countdown')) {
    updatePixCountdown();
    setInterval(updatePixCountdown, 1000);
}
</script>
