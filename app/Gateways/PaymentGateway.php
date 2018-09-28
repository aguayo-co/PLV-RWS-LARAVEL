<?php

namespace App\Gateways;

use App\Payment;

interface PaymentGateway
{
    public function getPaymentRequest($data);
    public function setCallback($data);
    public function getReference();
    public function validateCallbackData($data);
    public function getStatus();
    public function getData();
    public function getPaymentAmount();
    public function sendApprovedNotification();
    public function sendRejectedNotification();
    public function setPayment(Payment $payment);
}
