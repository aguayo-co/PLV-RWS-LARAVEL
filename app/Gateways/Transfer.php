<?php

namespace App\Gateways;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Response;
use App\Payment;

class Transfer implements PaymentGateway
{
    protected $callbackData;

    public function getPaymentRequest(Payment $payment, $data)
    {
        $payment->status = Payment::STATUS_PENDING;
        return [
            'public_data' => [
                'amount' => $payment->total,
                'reference' => $data['reference'],
            ]
        ];
    }

    protected function getPaymentStatus($status)
    {
        switch ($status) {
            case 'approved':
                return Payment::STATUS_SUCCESS;
            default:
                return Payment::STATUS_ERROR;
        }
    }

    public function validateCallbackData($data)
    {
        if (!auth()->user() || !auth()->user()->hasRole('admin')) {
            abort(Response::HTTP_FORBIDDEN);
        }
        if (!array_has($data, 'status') || !array_has($data, 'reference')) {
            abort(Response::HTTP_BAD_REQUEST);
        }
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
    }

    public function getStatus()
    {
        return $this->getPaymentStatus($this->callbackData['status']);
    }

    public function getReference()
    {
        return $this->callbackData['reference'];
    }

    public function getData()
    {
        return $this->callbackData;
    }
}
