# Implementação de Pagamento PIX com Abacate Pay

## Visão Geral

Este documento detalha a implementação da funcionalidade de pagamento PIX no checkout, utilizando a **Abacate Pay** como provedor. O objetivo foi criar uma experiência de pagamento fluida, independente e totalmente funcional, desde a seleção do método de pagamento até a confirmação e redirecionamento do usuário.

## Como a Funcionalidade Opera

O fluxo de pagamento foi projetado para ser intuitivo e claro para o usuário:

1.  **Seleção do Método de Pagamento**: Na página de checkout, o usuário verá duas opções de pagamento: "Cartão" e "PIX".
2.  **Abertura do Modal PIX**: Ao clicar no card "PIX", um modal independente é aberto, escurecendo o fundo da página para focar a atenção do usuário.
3.  **Resumo do Pedido e Formulário**:
    *   O topo do modal exibe um **resumo claro do pedido**, incluindo o nome do produto, o plano selecionado, o valor original, os descontos aplicados e o valor final a ser pago.
    *   Abaixo do resumo, um formulário solicita os dados necessários para a transação PIX: **Nome Completo**, **E-mail**, **Telefone** e **CPF**. Esses campos são independentes dos campos do formulário de cartão de crédito.
4.  **Geração do QR Code**:
    *   Ao clicar no botão "GERAR PIX", o sistema valida os dados do formulário. Um indicador de "processando" é exibido imediatamente para dar feedback ao usuário.
    *   Com os dados validados, o sistema faz uma chamada à API da Abacate Pay para criar a cobrança PIX.
    *   O conteúdo do modal é então substituído para exibir o **QR Code**, o código **"copia e cola"** e o **tempo de expiração** da cobrança.
5.  **Confirmação de Pagamento e Redirecionamento**:
    *   Enquanto o QR Code está visível, o sistema inicia um processo de verificação (polling) em segundo plano, consultando a API da Abacate Pay a cada poucos segundos para saber o status do pagamento.
    *   Quando o pagamento é confirmado (`PAID`), o usuário é **automaticamente redirecionado** para a página de sucesso.
    *   Se o pagamento falhar ou expirar (`FAILED` ou `EXPIRED`), o usuário é redirecionado para uma página de falha.

---

## Configuração para o Ambiente de Produção

Para que a funcionalidade opere corretamente em produção, é crucial configurar as seguintes variáveis e arquivos.

### 1. Variáveis de Ambiente (`.env`)

Adicione as seguintes chaves ao seu arquivo `.env` e preencha com suas credenciais de produção da Abacate Pay.

```dotenv
# CHAVE DA API DA ABACATE PAY
ABACATEPAY_API_KEY=abc_prod_sua_chave_de_producao_aqui

# URL DA API (geralmente não muda, mas é bom ter como variável)
ABACATEPAY_API_URL=https://api.abacatepay.com/v1

# TEMPO DE EXPIRAÇÃO DO PIX (em segundos)
# O padrão é 1800 segundos (30 minutos), mas você pode ajustar conforme necessário.
ABACATEPAY_PIX_EXPIRATION=1800
```

### 2. Configuração de Serviços (`config/services.php`)

Verifique se o arquivo `config/services.php` está configurado para ler as variáveis de ambiente da Abacate Pay. A estrutura deve ser a seguinte:

```php
// Em config/services.php

'abacatepay' => [
    'api_key' => env('ABACATEPAY_API_KEY'),
    'api_url' => env('ABACATEPAY_API_URL', 'https://api.abacatepay.com/v1'),
    'pix_expiration' => env('ABACATEPAY_PIX_EXPIRATION', 1800),
],
```

### 3. URLs de Redirecionamento no JavaScript

As URLs para as quais o usuário é redirecionado após o pagamento são definidas no arquivo `resources/views/livewire/page-pay.blade.php`. Você **precisa** alterar as URLs de exemplo para as suas URLs de produção.

Localize o seguinte trecho de código JavaScript no arquivo:

```javascript
document.addEventListener('livewire:init', () => {
    // ... outros listeners

    Livewire.on('pix-paid', () => {
        console.log('PIX pago! Redirecionando...');
        stopPixPolling();
        // 👇 ALTERE A URL ABAIXO PARA SUA PÁGINA DE SUCESSO
        window.location.href = 'https://seusite.com/obrigado';
    });

    Livewire.on('pix-failed', () => {
        console.log('PIX falhou! Redirecionando...');
        stopPixPolling();
        // 👇 ALTERE A URL ABAIXO PARA SUA PÁGINA DE FALHA
        window.location.href = 'https://seusite.com/falha-no-pagamento';
    });

    Livewire.on('pix-expired', () => {
        console.log('PIX expirou! Redirecionando...');
        stopPixPolling();
        // 👇 ALTERE A URL ABAIXO PARA SUA PÁGINA DE FALHA
        window.location.href = 'https://seusite.com/falha-no-pagamento';
    });
});
```

---

Com essas configurações, a integração com a Abacate Pay estará pronta para funcionar em seu ambiente de produção.
