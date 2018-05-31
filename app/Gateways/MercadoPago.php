<?php

namespace App\Gateways;

use Illuminate\Support\Facades\App;
use App\Payment;
use Illuminate\Http\Response;
use MP;

class MercadoPago implements PaymentGateway
{
    use AutoNotificationsTrait;

    protected $callbackData;
    protected $paymentInfo;
    protected $payment;

    protected function getAccessToken()
    {
        return env('MP_ACCESS_TOKEN');
    }

    protected function getClientId()
    {
        return env('MP_CLIENT_ID');
    }

    protected function getClientSecret()
    {
        return env('MP_CLIENT_SECRET');
    }

    protected function getCurrency()
    {
        return 'CLP';
    }

    public function getPaymentRequest($data)
    {
        $buyer = $this->payment->order->user;
        $mercadoPago = new MP($this->getClientId(), $this->getClientSecret());

        $preferenceData = [
            'items' => [
                [
                    'currency_id' => $this->GetCurrency(),
                    'quantity' => 1,
                    'unit_price' => $this->payment->total,
                ],
            ],
            'payer' => [
                'name' => $buyer->first_name,
                'surname' => $buyer->last_name,
                'email' => $buyer->email,
            ],
            'back_urls' => [
                'success' => $data['back_urls']['success'],
                'pending' => $data['back_urls']['pending'],
                'failure' => $data['back_urls']['failure'],
            ],
            'notification_url' => route('callback.gateway', ['gateway' => 'mercado-pago']),
            'external_reference' => $data['reference'],
        ];

        $preference = $mercadoPago->create_preference($preferenceData);
        if ($preference['status'] != 201) {
            abort(Response::HTTP_BAD_GATEWAY);
        }

        return [
            'preference' => $preference['response'],
            'public_data' => $preference['response']['init_point'],
        ];
    }

    protected function getPaymentStatus($status)
    {
        switch ($status) {
            case 'approved':
                return Payment::STATUS_SUCCESS;
            case 'pending':
            case 'rejected':
            case 'in_process':
                return Payment::STATUS_PENDING;
            default:
                return Payment::STATUS_ERROR;
        }
    }

    public function validateCallbackData($data)
    {
        if (!array_get($data, 'type') || !array_get($data, 'data') || !ctype_digit(array_get($data, 'data.id'))) {
            abort(Response::HTTP_BAD_REQUEST, __('Invalid callback: ERROR'));
        }
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
        $mercadoPago = new MP($this->getClientId(), $this->getClientSecret());

        // If it is a test, do nothing.
        if (array_get($data, 'type') === 'test') {
            abort(Response::HTTP_OK, __('Test callback: OK'));
        }

        // Anything but payment, ignore.
        if (array_get($data, 'type') !== 'payment') {
            abort(Response::HTTP_OK, __('Non payment callback: IGNORING'));
        }

        $paymentInfo = $mercadoPago->get_payment_info(array_get($data, 'data.id'));
        if ($paymentInfo['status'] != 200) {
            abort(Response::HTTP_BAD_GATEWAY, __('Invalid MercadoPago info: ERROR'));
        }

        $this->paymentInfo = $paymentInfo['response']['collection'];
    }

    public function getStatus()
    {
        return $this->getPaymentStatus($this->paymentInfo['status']);
    }

    public function getReference()
    {
        return $this->paymentInfo['external_reference'];
    }

    public function getData()
    {
        return $this->paymentInfo;
    }

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
