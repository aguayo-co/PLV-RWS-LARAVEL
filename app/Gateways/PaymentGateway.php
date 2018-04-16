<?php

namespace App\Gateways;

use App\Payment;

interface PaymentGateway
{
    public function getPaymentRequest(Payment $payment, $data);
    public function setCallback($data);
    public function getReference();
    public function validateCallbackData($data);
    public function getStatus();
    public function getData();
}
