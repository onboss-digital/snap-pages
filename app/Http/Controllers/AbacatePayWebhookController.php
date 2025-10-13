<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AbacatePayWebhookController extends Controller
{
    /**
     * Handle the incoming webhook from AbacatePay
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            // Log da requisição recebida
            Log::channel('webhooks')->info('AbacatePay: Webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);

            // Validar assinatura do webhook (se configurado)
            if (!$this->validateSignature($request)) {
                Log::channel('webhooks')->error('AbacatePay: Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Obter dados do webhook
            $payload = $request->all();
            $event = $payload['event'] ?? null;
            $billingData = $payload['data'] ?? [];

            if (!$event) {
                Log::channel('webhooks')->warning('AbacatePay: No event type in webhook');
                return response()->json(['error' => 'No event type'], 400);
            }

            // Processar evento
            switch ($event) {
                case 'billing.paid':
                case 'payment.success':
                    $this->handlePaymentSuccess($billingData);
                    break;

                case 'billing.expired':
                case 'payment.expired':
                    $this->handlePaymentExpired($billingData);
                    break;

                case 'billing.failed':
                case 'payment.failed':
                    $this->handlePaymentFailed($billingData);
                    break;

                default:
                    Log::channel('webhooks')->info('AbacatePay: Unhandled event type', [
                        'event' => $event,
                    ]);
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::channel('webhooks')->error('AbacatePay: Exception processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Validate webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private function validateSignature(Request $request): bool
    {
        $webhookSecret = config('services.abacatepay.webhook_secret');
        
        // Se não houver secret configurado, aceitar (para testes)
        if (empty($webhookSecret)) {
            Log::channel('webhooks')->warning('AbacatePay: Webhook secret not configured, skipping validation');
            return true;
        }

        // Obter assinatura do header
        $signature = $request->header('X-AbacatePay-Signature');
        
        if (!$signature) {
            return false;
        }

        // Calcular hash esperado
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle successful payment
     *
     * @param array $data
     * @return void
     */
    private function handlePaymentSuccess(array $data): void
    {
        $billingId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;

        Log::channel('webhooks')->info('AbacatePay: Payment successful', [
            'billing_id' => $billingId,
            'status' => $status,
            'customer_email' => $customerEmail,
        ]);

        // TODO: Implementar lógica de negócio
        // 1. Atualizar status da transação no banco de dados
        // 2. Ativar assinatura do usuário
        // 3. Enviar email de confirmação
        // 4. Registrar no sistema de analytics
        
        // Exemplo:
        // $transaction = Transaction::where('pix_id', $billingId)->first();
        // if ($transaction) {
        //     $transaction->update(['status' => 'PAID']);
        //     $user = $transaction->user;
        //     $user->activateSubscription();
        //     Mail::to($user->email)->send(new PaymentConfirmed($transaction));
        // }
    }

    /**
     * Handle expired payment
     *
     * @param array $data
     * @return void
     */
    private function handlePaymentExpired(array $data): void
    {
        $billingId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        Log::channel('webhooks')->info('AbacatePay: Payment expired', [
            'billing_id' => $billingId,
            'status' => $status,
        ]);

        // TODO: Implementar lógica de negócio
        // 1. Atualizar status da transação no banco de dados
        // 2. Enviar email notificando expiração
        // 3. Registrar no sistema de analytics
        
        // Exemplo:
        // $transaction = Transaction::where('pix_id', $billingId)->first();
        // if ($transaction) {
        //     $transaction->update(['status' => 'EXPIRED']);
        // }
    }

    /**
     * Handle failed payment
     *
     * @param array $data
     * @return void
     */
    private function handlePaymentFailed(array $data): void
    {
        $billingId = $data['id'] ?? null;
        $status = $data['status'] ?? null;

        Log::channel('webhooks')->info('AbacatePay: Payment failed', [
            'billing_id' => $billingId,
            'status' => $status,
        ]);

        // TODO: Implementar lógica de negócio
        // 1. Atualizar status da transação no banco de dados
        // 2. Enviar email notificando falha
        // 3. Registrar no sistema de analytics
        
        // Exemplo:
        // $transaction = Transaction::where('pix_id', $billingId)->first();
        // if ($transaction) {
        //     $transaction->update(['status' => 'FAILED']);
        // }
    }
}
