/**
 * PIX Payment Handler
 * Gerencia polling automático e interações com pagamento PIX
 */

class PixPaymentHandler {
    constructor() {
        this.pollingInterval = null;
        this.pollingFrequency = 5000; // 5 segundos
        this.maxPollingAttempts = 360; // 30 minutos (360 * 5s)
        this.currentAttempts = 0;
    }

    /**
     * Inicia o polling automático para verificar status do PIX
     */
    startPolling() {
        if (this.pollingInterval) {
            this.stopPolling();
        }

        console.log('[PIX] Iniciando polling automático...');
        this.currentAttempts = 0;

        this.pollingInterval = setInterval(() => {
            this.currentAttempts++;

            // Verificar se atingiu o máximo de tentativas
            if (this.currentAttempts >= this.maxPollingAttempts) {
                console.log('[PIX] Máximo de tentativas atingido. Parando polling.');
                this.stopPolling();
                return;
            }

            // Chamar o método Livewire para verificar status
            if (window.Livewire) {
                console.log(`[PIX] Verificando status (tentativa ${this.currentAttempts})...`);
                window.Livewire.dispatch('checkPixStatus');
            }
        }, this.pollingFrequency);
    }

    /**
     * Para o polling automático
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('[PIX] Polling interrompido.');
        }
    }

    /**
     * Copia o código PIX para a área de transferência
     */
    copyPixCode(brCode) {
        if (!brCode) {
            console.error('[PIX] Código PIX não fornecido');
            return false;
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(brCode)
                .then(() => {
                    console.log('[PIX] Código copiado com sucesso');
                    this.showCopyFeedback();
                    return true;
                })
                .catch(err => {
                    console.error('[PIX] Erro ao copiar:', err);
                    this.fallbackCopy(brCode);
                });
        } else {
            this.fallbackCopy(brCode);
        }
    }

    /**
     * Método alternativo para copiar (navegadores antigos)
     */
    fallbackCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                console.log('[PIX] Código copiado (fallback)');
                this.showCopyFeedback();
            }
        } catch (err) {
            console.error('[PIX] Erro ao copiar (fallback):', err);
        }

        document.body.removeChild(textArea);
    }

    /**
     * Mostra feedback visual de cópia
     */
    showCopyFeedback() {
        // Tentar mostrar toast do Livewire se disponível
        if (window.Livewire) {
            window.Livewire.dispatch('notify', {
                message: 'Código PIX copiado!',
                type: 'success'
            });
        }
    }

    /**
     * Atualiza o contador regressivo de expiração
     */
    updateCountdown(expiresAt) {
        const countdownEl = document.getElementById('pix-countdown');
        if (!countdownEl) return;

        const expiryTime = new Date(expiresAt).getTime();
        const now = new Date().getTime();
        const distance = expiryTime - now;

        if (distance < 0) {
            countdownEl.textContent = 'Expirado';
            countdownEl.classList.add('text-red-500');
            this.stopPolling();
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        // Avisar quando estiver próximo da expiração
        if (distance < 60000 && !countdownEl.classList.contains('text-red-500')) {
            countdownEl.classList.add('text-red-500', 'animate-pulse');
        }
    }

    /**
     * Inicializa o contador regressivo
     */
    initCountdown(expiresAt) {
        if (!expiresAt) return;

        this.updateCountdown(expiresAt);
        setInterval(() => this.updateCountdown(expiresAt), 1000);
    }

    /**
     * Previne QR Code de piscar (evita re-renders desnecessários)
     */
    preventQrFlicker() {
        const qrImage = document.getElementById('pix-qr-image');
        if (qrImage && !qrImage.dataset.loaded) {
            qrImage.addEventListener('load', () => {
                qrImage.dataset.loaded = 'true';
            });
        }
    }
}

// Instância global
const pixHandler = new PixPaymentHandler();

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('[PIX] Handler inicializado');

    // Verificar se estamos na página de pagamento PIX
    const pixCard = document.querySelector('[data-pix-card]');
    if (pixCard) {
        const expiresAt = pixCard.dataset.expiresAt;
        
        // Iniciar polling automático
        pixHandler.startPolling();
        
        // Iniciar contador se houver data de expiração
        if (expiresAt) {
            pixHandler.initCountdown(expiresAt);
        }

        // Prevenir flicker do QR Code
        pixHandler.preventQrFlicker();
    }
});

// Listener para eventos Livewire
document.addEventListener('livewire:initialized', () => {
    console.log('[PIX] Livewire inicializado');

    // Escutar mudanças no status do PIX
    Livewire.on('pixStatusChanged', (event) => {
        const status = event.status;
        console.log('[PIX] Status alterado:', status);

        if (status === 'PAID') {
            pixHandler.stopPolling();
            console.log('[PIX] Pagamento confirmado! Redirecionando...');
        } else if (['EXPIRED', 'FAILED'].includes(status)) {
            pixHandler.stopPolling();
            console.log('[PIX] Pagamento finalizado:', status);
        }
    });

    // Escutar quando PIX é criado
    Livewire.on('pixCreated', (event) => {
        console.log('[PIX] PIX criado, iniciando polling...');
        pixHandler.startPolling();

        if (event.expiresAt) {
            pixHandler.initCountdown(event.expiresAt);
        }
    });
});

// Parar polling quando sair da página
window.addEventListener('beforeunload', () => {
    pixHandler.stopPolling();
});

// Exportar para uso global
window.pixHandler = pixHandler;