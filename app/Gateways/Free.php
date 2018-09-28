<?php

namespace App\Gateways;

use App\Order;
use App\Payment;

class Free implements PaymentGateway
{
    use AutoNotificationsTrait;

    public function getPaymentRequest($data)
    {
        $this->payment->status = Payment::STATUS_SUCCESS;
        return [];
    }

    public function validateCallbackData($data)
    {
    }

    public function setCallback($data)
    {
    }

    public function getStatus()
    {
        return Payment::STATUS_SUCCESS;
    }

    public function getReference()
    {
    }

    public function getData()
    {
    }

    public function getPaymentAmount()
    {
        return 0;
    }

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
