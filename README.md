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
