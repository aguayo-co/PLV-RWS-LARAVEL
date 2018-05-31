<?php

namespace App\Gateways;

use App\Order;
use App\Payment;

class Free implements PaymentGateway
{
    public function getPaymentRequest($data)
    {
        $this->payment->status = Payment::STATUS_SUCCESS;
        return [];
    }

    public function sendApprovedNotification()
    {
    }

    public function sendRejectedNotification()
    {
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

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
