# Implementação de Pagamento PIX com Abacate Pay

## Visão Geral

Este documento detalha a implementação da funcionalidade de pagamento PIX no checkout, utilizando a **Abacate Pay** como provedor. O objetivo foi criar uma experiência de pagamento fluida e totalmente independente de serviços externos para a lógica de preços.

## Como a Funcionalidade Opera

O fluxo de pagamento foi projetado para ser intuitivo e claro para o usuário:

1.  **Seleção do Método de Pagamento**: Na página de checkout, o usuário verá duas opções de pagamento: "Cartão" e "PIX".
2.  **Abertura do Modal PIX**: Ao clicar no card "PIX", um modal independente é aberto.
3.  **Resumo do Pedido e Formulário**:
    *   O topo do modal exibe um **resumo claro do pedido**. As informações de preço (valor, descontos, etc.) são lidas de um arquivo de configuração local (`config/plans.php`).
    *   Abaixo do resumo, um formulário solicita os dados necessários para a transação PIX: **Nome Completo**, **E-mail**, **Telefone** e **CPF**.
4.  **Geração do QR Code**:
    *   Ao clicar em "GERAR PIX", o sistema valida os dados e exibe um indicador de "processando".
    *   Com os dados validados, o sistema faz uma chamada à API da Abacate Pay, enviando o valor final (lido do arquivo de configuração local) para criar a cobrança PIX.
    *   O modal é atualizado para exibir o **QR Code**, o código **"copia e cola"** e o **tempo de expiração**.
5.  **Confirmação de Pagamento e Redirecionamento**:
    *   O sistema verifica o status do pagamento em segundo plano.
    *   Quando o pagamento é confirmado (`PAID`), o usuário é **automaticamente redirecionado** para a página de sucesso.
    *   Em caso de falha ou expiração, o usuário é redirecionado para uma página de falha.

---

## Configuração do Projeto

Para que a funcionalidade opere corretamente, é crucial configurar os seguintes arquivos.

### 1. Arquivo de Configuração de Planos (`config/plans.php`)

Esta é a **fonte da verdade** para os planos e preços. A dependência de APIs externas para preços foi removida para garantir total autonomia.

**Exemplo de configuração para um plano mensal:**

```php
// Em config/plans.php

return [
    'monthly' => [
        'id' => 'prod_1MXtRjLJfwbabM1aYeYtX2h3', // ID do Produto na Abacate Pay (usado para referência interna)
        'price' => 2490, // Preço final em centavos (ex: R$ 24,90)
        'label' => 'Plano Mensal',
        'original_price' => 4990, // Preço original em centavos (usado para mostrar o desconto)
    ],
    // Você pode adicionar outros planos aqui no futuro
];
```

**Para alterar o preço ou o ID do produto, modifique este arquivo.**

### 2. Variáveis de Ambiente (`.env`)

Adicione as seguintes chaves ao seu arquivo `.env` com as suas credenciais da Abacate Pay.

```dotenv
# CHAVE DA API DA ABACATE PAY
ABACATEPAY_API_KEY=abc_sua_chave_aqui

# URL DA API (geralmente não muda)
ABACATEPAY_API_URL=https://api.abacatepay.com/v1

# TEMPO DE EXPIRAÇÃO DO PIX (em segundos)
ABACATEPAY_PIX_EXPIRATION=1800
```

### 3. Configuração de Serviços (`config/services.php`)

Garanta que o arquivo `config/services.php` esteja configurado para ler as variáveis de ambiente da Abacate Pay.

```php
// Em config/services.php

'abacatepay' => [
    'api_key' => env('ABACATEPAY_API_KEY'),
    'api_url' => env('ABACATEPAY_API_URL', 'https://api.abacatepay.com/v1'),
    'pix_expiration' => env('ABACATEPAY_PIX_EXPIRATION', 1800),
],
```

### 4. URLs de Redirecionamento no JavaScript

As URLs de redirecionamento (sucesso/falha) após o pagamento são definidas no arquivo `resources/views/livewire/page-pay.blade.php`. **Você precisa alterar estas URLs para as suas URLs de produção.**

```javascript
// Em resources/views/livewire/page-pay.blade.php

Livewire.on('pix-paid', () => {
    // 👇 ALTERE A URL ABAIXO
    window.location.href = 'https://seusite.com/obrigado';
});

Livewire.on('pix-failed', () => {
    // 👇 ALTERE A URL ABAIXO
    window.location.href = 'https://seusite.com/falha-no-pagamento';
});

Livewire.on('pix-expired', () => {
    // 👇 ALTERE A URL ABAIXO
    window.location.href = 'https://seusite.com/falha-no-pagamento';
});
```
---

Com essas configurações, a integração está completa, funcional e totalmente independente para a gestão de preços.
