# SnapHub Pages

## Visão Geral
O SnapHub Pages é uma plataforma especializada na criação e gerenciamento de páginas de vendas, landing pages e exibição de produtos para o ecossistema SnapHub. A aplicação permite criar, personalizar e publicar rapidamente páginas para:

## Tecnologias Utilizadas
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend Interativo**: Livewire 3.x
- **CSS**: TailwindCSS 4.x
- **Bancos de Dados**: MySQL/PostgreSQL

## Requisitos do Sistema
- PHP 8.2 ou superior
- Composer
- Node.js 16+ e NPM
- MySQL 8.0 ou PostgreSQL 12+

## Instalação

```bash
# Clone o repositório
git clone https://github.com/IsottonTecnologia/snaphubb-pages.git
cd snaphubb-pages

# Instalar dependências PHP
composer install

# Instalar dependências JavaScript
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no arquivo .env e executar migrações
php artisan migrate

# Compilar assets
npm run dev

# Iniciar servidor de desenvolvimento
php artisan serve
```

## Payment Gateway Configuration

This application supports multiple payment gateways. The active gateway can be configured via environment variables.

### Configuring the Active Gateway

1.  Open your `.env` file.
2.  Set the `DEFAULT_PAYMENT_GATEWAY` variable to the desired gateway's key. Currently supported keys are:
    *   `tribopay`
    *   `for4payment` (Note: This is a placeholder implementation)

    Example:
    ```env
    DEFAULT_PAYMENT_GATEWAY=tribopay
    ```

3.  Ensure that the specific configuration for the chosen gateway is also present in the `.env` file.

    For TriboPay:
    ```env
    TRIBO_PAY_API_TOKEN=your_tribopay_api_token
    TRIBO_PAY_API_URL=https://api.tribopay.com.br
    ```

    For For4Payment (placeholder):
    ```env
    FOR4PAYMENT_API_KEY=your_for4payment_api_key
    FOR4PAYMENT_API_URL=https://api.for4payment.com
    ```

### Adding a New Payment Gateway

To add support for a new payment gateway, follow these steps:

1.  **Create a Gateway Class**:
    *   Create a new class in the `app/Services/PaymentGateways/` directory (e.g., `NewGatewayNameGateway.php`).
    *   This class must implement the `App\Interfaces\PaymentGatewayInterface`.
    *   Implement the required methods: `createCardToken(array $cardData): array`, `processPayment(array $paymentData): array`, and `handleResponse(array $responseData, int $statusCode): array`. Refer to existing gateways for examples.

2.  **Add Configuration**:
    *   Add configuration keys for the new gateway in `config/services.php`. This typically includes API keys, URLs, etc.
        ```php
        // In config/services.php
        'newgatewayname' => [
            'api_key' => env('NEWGATEWAYNAME_API_KEY'),
            'api_url' => env('NEWGATEWAYNAME_API_URL'),
            // other config
        ],
        ```
    *   Add corresponding environment variables to your `.env.example` file and instruct users to add them to their `.env` file.
        ```env
        # In .env.example
        NEWGATEWAYNAME_API_KEY=
        NEWGATEWAYNAME_API_URL=
        ```

3.  **Register in Factory**:
    *   Update `app/Factories/PaymentGatewayFactory.php` to include a case for your new gateway:
        ```php
        // In PaymentGatewayFactory.php
        case 'newgatewayname': // Use a simple key for the gateway
            return new NewGatewayNameGateway();
        ```

4.  **Testing**:
    *   Write unit tests for your new gateway class (`tests/Unit/Services/PaymentGateways/NewGatewayNameGatewayTest.php`).
    *   Update or add integration tests in `tests/Feature/Livewire/PagePayTest.php` to cover checkout flows using your new gateway (mocking its external API calls).

By following these steps, you can extend the application to support various payment providers while keeping the core checkout logic decoupled.
# Implementação de Pagamento PIX com Abacate Pay

Este documento detalha a implementação da funcionalidade de pagamento PIX utilizando a Abacate Pay.

## Arquivos Criados

- : Este arquivo contém a lógica de integração com a API da Abacate Pay. Ele é responsável por criar a cobrança PIX e verificar o status do pagamento.

## Arquivos Modificados

- : Este arquivo foi modificado para incluir a  como um gateway de pagamento disponível.
- : O componente Livewire foi atualizado para manipular o fluxo de pagamento PIX. As principais alterações incluem:
    - Despachar eventos Livewire (, , ) para o front-end.
    - Chamar o  para criar cobranças PIX e verificar o status do pagamento.
- : O arquivo de visualização foi atualizado para incluir o polling de JavaScript para o status do PIX.

## Configuração de Produção

Para configurar o ambiente de produção, os seguintes arquivos precisam ser atualizados:

- :
    - : Defina para sua chave de API de produção da Abacate Pay.
    - : Defina para o URL da API de produção da Abacate Pay.
    - : Defina como  se o PIX for o método de pagamento padrão.
- :
    - Configure as credenciais da Abacate Pay no array .



- :
    - Atualize os URLs de redirecionamento para  e  para seus URLs de produção.



# Implementação de Pagamento PIX com Abacate Pay

Este documento detalha a implementação da funcionalidade de pagamento PIX utilizando a Abacate Pay.

## Arquivos Criados

- `app/Services/PaymentGateways/AbacatePayGateway.php`: Este arquivo contém a lógica de integração com a API da Abacate Pay. Ele é responsável por criar a cobrança PIX e verificar o status do pagamento.

## Arquivos Modificados

- `app/Factories/PaymentGatewayFactory.php`: Este arquivo foi modificado para incluir a `AbacatePayGateway` como um gateway de pagamento disponível.
- `app/Livewire/PagePay.php`: O componente Livewire foi atualizado para manipular o fluxo de pagamento PIX. As principais alterações incluem:
    - Despachar eventos Livewire (`pix-generated`, `pix-paid`, `pix-failed`) para o front-end.
    - Chamar o `AbacatePayGateway` para criar cobranças PIX e verificar o status do pagamento.
- `resources/views/livewire/page-pay.blade.php`: O arquivo de visualização foi atualizado para incluir o polling de JavaScript para o status do PIX.

## Configuração de Produção

Para configurar o ambiente de produção, os seguintes arquivos precisam ser atualizados:

- `.env`:
    - `ABACATEPAY_API_KEY`: Defina para sua chave de API de produção da Abacate Pay.
    - `ABACATEPAY_API_URL`: Defina para o URL da API de produção da Abacate Pay.
    - `DEFAULT_PAYMENT_GATEWAY`: Defina como `abacatepay` se o PIX for o método de pagamento padrão.
- `config/services.php`:
    - Configure as credenciais da Abacate Pay no array `abacatepay`.

```php
'abacatepay' => [
    'api_key' => env('ABACATEPAY_API_KEY'),
    'api_url' => env('ABACATEPAY_API_URL', 'https://api.abacatepay.com/v1'),
    'pix_expiration' => env('ABACATEPAY_PIX_EXPIRATION', 1800), // in seconds
],
```

- `resources/views/livewire/page-pay.blade.php`:
    - Atualize os URLs de redirecionamento para `pix-paid` e `pix-failed` para seus URLs de produção.

```javascript
Livewire.on('pix-paid', () => {
    console.log('PIX pago! Redirecionando...');
    stopPixPolling();
    window.location.href = 'SEU_URL_DE_SUCESSO_AQUI';
});

Livewire.on('pix-failed', () => {
    console.log('PIX falhou! Redirecionando...');
    stopPixPolling();
    window.location.href = 'SEU_URL_DE_FALHA_AQUI';
});
```
