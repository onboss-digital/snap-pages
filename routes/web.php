<?php

use App\Http\Controllers\MercadoPagoWebhookController;
use App\Livewire\PagePay;
use App\Http\Controllers\Webhook\AbacatePayWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página principal (checkout) - Livewire Component
Route::get('/', PagePay::class);

// ===== WEBHOOK ABACATEPAY =====
Route::post('/webhook/abacatepay', [AbacatePayWebhookController::class, 'handle'])
    ->name('webhook.abacatepay');

// ===== WEBHOOK MERCADO PAGO =====
Route::post('/webhook/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
    ->name('webhook.mercadopago');

// ===== PÁGINAS DE RETORNO PIX =====

// Página de sucesso após pagamento PIX
Route::get('/obg/', function () {
    return view('obg');
})->name('payment.success');

// Página de falha/expiração PIX (opcional)
Route::get('/fail-pix/', function () {
    return view('fail-pix');
})->name('payment.failed');
