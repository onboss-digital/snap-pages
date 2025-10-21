<!-- PIX Modal -->
<div id="pix-modal" x-data="{ show: $wire.entangle('showPixModal') }" x-show="show" x-cloak
    class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 transition-opacity duration-300"
    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    <div @click.away="show = false"
        class="bg-[#1F1F1F] rounded-xl shadow-2xl max-w-lg w-full m-4 max-h-[90vh] overflow-y-auto transform transition-all duration-300"
        x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        <div class="p-6 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white">Pagamento via PIX</h3>
                <button @click="show = false" class="text-gray-400 hover:text-white transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Estado 1: Formulário de dados do cliente --}}
            @if (empty($pixResult))
            <div class="space-y-4">
                <!-- Resumo do Pedido -->
                <div class="bg-gray-800 rounded-lg p-4 flex items-center">
                    <img src="https://web.snaphubb.online/wp-content/uploads/2025/10/capa-brasil.jpg" alt="Produto" class="w-20 h-20 rounded-lg mr-4 object-cover">
                    <div>
                        <h4 class="text-white font-semibold">Produto Teste PIX</h4>
                        <p class="text-gray-400 text-sm">SnapHubb es tu nueva plataforma global de streaming para adultos. Un catálogo internacional con creadores independientes, películas para adultos.</p>
                        <div class="mt-2">
                            <span class="text-gray-400 text-sm line-through">R$47,90</span>
                            <span class="text-green-400 text-xl font-bold ml-2">R$24,90</span>
                            <span class="bg-green-500 text-white text-xs font-semibold ml-2 px-2 py-1 rounded-full">Você ganhou 47% de desconto!</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="pixName" class="block text-sm font-medium text-gray-300 mb-1">Nome Completo</label>
                    <input type="text" id="pixName" wire:model.defer="pixName" placeholder="Seu nome completo"
                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                    @error('pixName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="pixEmail" class="block text-sm font-medium text-gray-300 mb-1">E-mail</label>
                    <input type="email" id="pixEmail" wire:model.defer="pixEmail" placeholder="seu@email.com"
                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                    @error('pixEmail') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="pixCpf" class="block text-sm font-medium text-gray-300 mb-1">CPF</label>
                    <input type="text" id="pixCpf" wire:model.defer="pixCpf" x-mask="999.999.999-99" placeholder="000.000.000-00"
                        class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                    @error('pixCpf') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button wire:click="generatePixOrder" wire:loading.attr="disabled"
                            class="w-full bg-red-600 hover:bg-red-700 text-white py-3 text-lg font-bold rounded-xl transition-all transform hover:scale-105 flex items-center justify-center">
                        <span wire:loading.remove wire:target="generatePixOrder">GERAR PIX</span>
                        <span wire:loading wire:target="generatePixOrder">Gerando...</span>
                    </button>
                </div>
            </div>

            {{-- Estado 2: Exibição do QR Code e Código --}}
            @else
                @if($pixResult['status'] === 'approved')
                {{-- Estado 2.1: Pagamento Aprovado --}}
                <div class="text-center py-8">
                    <div class="flex justify-center mb-4">
                        <svg class="h-20 w-20 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-2xl font-bold text-white mb-2">Pagamento Aprovado!</h4>
                    <p class="text-gray-300 mb-6">Sua compra foi concluída com sucesso. Verifique seu e-mail para mais detalhes.</p>
                    <button @click="show = false" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 font-bold rounded-xl transition-all">
                        Fechar
                    </button>
                </div>
                @else
                {{-- Estado 2.2: Aguardando Pagamento --}}
                <div class="text-center" wire:poll.3000ms="checkPixStatus">
                    <h4 class="text-lg font-semibold text-white mb-2">Pague com PIX para finalizar sua compra</h4>
                    <p class="text-gray-400 mb-6">Escaneie o QR Code ou use o código abaixo.</p>

                    <div class="flex justify-center mb-4">
                        <img src="data:image/jpeg;base64,{{ $pixResult['qr_code_base64'] }}" alt="PIX QR Code" class="w-64 h-64 rounded-lg border-4 border-white">
                    </div>

                    <div class="mb-6">
                        <label class="text-sm font-medium text-gray-300 mb-1">PIX Copia e Cola</label>
                        <div class="relative">
                            <input type="text" readonly value="{{ $pixResult['qr_code'] }}"
                                   class="w-full bg-[#2D2D2D] text-white rounded-lg p-3 border border-gray-700 pr-12 text-center text-sm">
                            <button onclick="navigator.clipboard.writeText('{{ $pixResult['qr_code'] }}')"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-white">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m-8.5 2H9m4.5 0H15m-2.25 2.25V18m0-2.25v-2.25m0 5.25v-2.25m-2.25 0h-1.5m1.5 0h1.5"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg p-4 text-left text-sm">
                        <p class="font-semibold text-white mb-2">Instruções:</p>
                        <ol class="list-decimal list-inside text-gray-300 space-y-1">
                            <li>Abra o aplicativo do seu banco.</li>
                            <li>Escolha a opção de pagamento via PIX.</li>
                            <li>Escaneie o QR Code ou cole o código acima.</li>
                            <li>Confirme o pagamento e pronto!</li>
                        </ol>
                    </div>

                    <div class="mt-6">
                        <p class="text-yellow-400 animate-pulse">Aguardando pagamento...</p>
                    </div>
                </div>
                @endif
            @endif

        </div>
    </div>
</div>
