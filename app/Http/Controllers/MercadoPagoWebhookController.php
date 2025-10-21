<?php

namespace App\Http\Controllers;

use App\Models\PixOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Handle incoming Mercado Pago webhooks.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request): Response
    {
        Log::channel('payment_checkout')->info('Mercado Pago Webhook received', ['payload' => $request->all()]);

        $payload = $request->all();

        // Verificar se é um evento de pagamento
        if (isset($payload['type']) && $payload['type'] === 'payment') {
            $paymentId = $payload['data']['id'] ?? null;

            if ($paymentId) {
                // Aqui, em um cenário real, faríamos outra chamada à API do Mercado Pago
                // para obter os detalhes completos do pagamento e verificar seu status.
                // $paymentDetails = $mercadoPagoGateway->getPaymentDetails($paymentId);
                // No entanto, para este escopo, vamos simular a atualização com base no que temos.

                // Vamos assumir que o webhook só é enviado em caso de sucesso para simplificar.
                // A lógica real deve ser mais robusta.

                $order = PixOrder::where('mercado_pago_id', $paymentId)->first();

                if ($order && $order->status !== 'approved') {
                    // Simplesmente atualizamos o status para "approved"
                    // A validação real viria da consulta à API
                    $order->status = 'approved';
                    $order->paid_at = now();
                    $order->save();

                    Log::channel('payment_checkout')->info('PIX order updated to approved via webhook.', ['order_id' => $order->id, 'mp_id' => $paymentId]);
                }
            }
        }

        return response('Webhook received', 200);
    }
}
