<?php

namespace App\Gateways;

use App\Order;
use App\Payment;

class Free implements PaymentGateway
{
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getPaymentRequest(Payment $payment, $data)
    {
        $payment->status = Payment::STATUS_SUCCESS;
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
}
