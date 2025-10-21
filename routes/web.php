<?php

use App\Livewire\PagePay;
use App\Http\Controllers\AbacatePayWebhookController;
use App\Http\Controllers\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página principal (checkout) - Livewire Component
Route::get('/', PagePay::class);

// ===== WEBHOOKS =====
Route::post('/webhook/abacatepay', [AbacatePayWebhookController::class, 'handle'])
    ->name('webhook.abacatepay');

Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
    ->name('webhooks.mercadopago');


// ===== PÁGINAS DE RETORNO PIX =====

// Página de sucesso após pagamento PIX
Route::get('/obg-br', function () {
    return "Página de Obrigado"; // Placeholder
})->name('payment.success');

// Página de falha/expiração PIX
Route::get('/fail-br', function () {
    return "Página de Falha"; // Placeholder
})->name('payment.failed');
