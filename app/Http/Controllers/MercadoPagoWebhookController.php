<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from Mercado Pago.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        // Log the incoming request for debugging purposes
        Log::channel('webhooks')->info('Mercado Pago Webhook Received:', $request->all());

        // A lógica para processar o webhook (atualizar o status do pedido, etc.)
        // seria implementada aqui.

        // Retorna uma resposta 200 OK para o Mercado Pago confirmar o recebimento.
        return response()->json(['status' => 'ok'], 200);
    }
}
