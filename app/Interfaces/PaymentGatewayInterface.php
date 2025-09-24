<?php

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    public function createCardToken(array $cardData): array;

    public function processPayment(array $paymentData): array;

    public function handleResponse(array $responseData): array;

    public function formatPlans(mixed $data, string $selectedCurrency): array;
}
