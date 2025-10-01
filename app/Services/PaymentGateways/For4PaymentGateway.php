<?php

namespace App\Services\PaymentGateways;

use App\Interfaces\PaymentGatewayInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class For4PaymentGateway implements PaymentGatewayInterface
{
    protected $httpClient;
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->httpClient = new Client();
        // It's good practice to use config values for API keys and URLs
        $this->apiKey = config('services.for4payment.api_key');
        $this->apiUrl = config('services.for4payment.api_url');
    }

    public function createCardToken(array $cardData): array
    {
        // Placeholder: Actual For4Payment tokenization logic would go here.
        // This might involve an API call to For4Payment to get a token.
        Log::channel('payment_checkout')->info('For4PaymentGateway: Creating card token (placeholder).', $cardData);

        // Simulate a successful tokenization
        return [
            'status' => 'success',
            'token' => 'for4payment_fake_token_' . uniqid(),
            'token_data' => $cardData // Or whatever For4Payment returns
        ];
    }

    public function processPayment(array $paymentData): array
    {
        // Placeholder: Actual For4Payment payment processing logic.
        // This will involve an API call to For4Payment.

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Mask sensitive data for logging
        $loggedData = $paymentData;
        if (isset($loggedData['card_token'])) { // Assuming payment is made with a token
            // Potentially mask or shorten token if needed, though often tokens are safe to log
        } else if (isset($loggedData['card']['number'])) { // If raw card details are sent (less ideal)
            $loggedData['card']['number'] = '**** **** **** ' . substr($paymentData['card']['number'], -4);
            $loggedData['card']['cvv'] = '***';
        }


        Log::channel('payment_checkout')->info('For4PaymentGateway: Processing payment (placeholder). Data:', $loggedData);

        // Simulate an API call
        try {
            $request = new Request(
                'POST',
                $this->apiUrl . '/payments', // Example endpoint
                $headers,
                json_encode($paymentData)
            );

            $res = $this->httpClient->sendAsync($request)->wait();
            $responseBody = $res->getBody()->getContents();
            $dataResponse = json_decode($responseBody, true);

            Log::channel('payment_checkout')->info('For4PaymentGateway: API Response (placeholder).', [
                'status_code' => $res->getStatusCode(),
                'body' => $dataResponse,
            ]);

            return $this->handleResponse($dataResponse, $res->getStatusCode());

        } catch (\Exception $e) {
            Log::channel('payment_checkout')->error('For4PaymentGateway: API Error (placeholder).', [
                'message' => $e->getMessage(),
                'request_data' => $loggedData,
            ]);
            return [
                'status' => 'error',
                'message' => 'Error processing payment with For4Payment: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }

        // Simulate a successful payment response for now
        $simulatedResponse = [
            'success' => true,
            'transaction_id' => 'f4p_txn_' . uniqid(),
            'amount' => $paymentData['amount'] ?? 0,
            'currency' => $paymentData['currency'] ?? 'BRL',
            'message' => 'Payment processed successfully (simulated by For4PaymentGateway).'
        ];
        return $this->handleResponse($simulatedResponse, 200);
    }

    public function handleResponse(array $responseData, int $statusCode = 200): array
    {
        // Placeholder: Actual For4Payment response handling.
        Log::channel('payment_checkout')->info('For4PaymentGateway: Handling response (placeholder).', $responseData);

        if ($statusCode >= 200 && $statusCode < 300 && isset($responseData['success']) && $responseData['success']) {
            return [
                'status' => 'success',
                'transaction_id' => $responseData['transaction_id'] ?? null,
                'message' => $responseData['message'] ?? 'Payment successful.',
                'data' => $responseData
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $responseData['message'] ?? 'Payment failed with For4Payment.',
                'errors' => $responseData['errors'] ?? [],
                'original_response' => $responseData
            ];
        }
    }

    public function formatPlans(mixed $data, string $selectedCurrency): array
    {
        // Placeholder implementation to satisfy the interface.
        return [];
    }
}
