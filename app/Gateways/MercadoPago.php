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

    protected function getAmount()
    {
        return (int) ($this->payment->total * (100 + config('prilov.payments.percentage_fee.mercado_pago')) / 100);
    }

    public function getMercadoPago()
    {
        if ($this->getAccessToken()) {
            $mercadoPago = new MP($this->getAccessToken());
        } else {
            $mercadoPago = new MP($this->getClientId(), $this->getClientSecret());
        }

        return $mercadoPago;
    }

    public function getPaymentRequest($data)
    {
        $buyer = $this->payment->order->user;
        $mercadoPago = $this->getMercadoPago();


        $preferenceData = [
            'items' => [
                [
                    'currency_id' => $this->GetCurrency(),
                    'quantity' => 1,
                    'unit_price' => $this->getAmount(),
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

    protected function getPaymentId($data)
    {
        if (array_has($data, 'topic')) {
            return array_get($data, 'id');
        }

        if (array_get($data, 'type')) {
            return array_get($data, 'data.id');
        }

        return array_get($data, 'collection_id');
    }

    public function validateCallbackData($data)
    {
        // IPN
        // https://www.mercadopago.cl/developers/es/api-docs/basic-checkout/ipn/
        if (array_has($data, 'topic') && ctype_digit(array_get($data, 'id'))) {
            return;
        }

        // Webhooks
        // https://www.mercadopago.cl/developers/es/solutions/payments/custom-checkout/webhooks/
        if (array_has($data, 'type') && ctype_digit(array_get($data, 'data.id'))) {
            return;
        }

        // User response page
        if (array_has($data, 'collection_id')) {
            return;
        }

        abort(Response::HTTP_BAD_REQUEST, __('Invalid callback: ERROR'));
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
        $mercadoPago = $this->getMercadoPago();

        // If it is a test, do nothing.
        if (array_get($data, 'type') === 'test') {
            abort(Response::HTTP_OK, __('Test callback: OK'));
        }

        // Anything but payment, ignore.
        if (
            array_get($data, 'type') !== 'payment' &&
            array_get($data, 'topic') !== 'payment' &&
            !array_has($data, 'merchant_order_id')
        ) {
            abort(Response::HTTP_OK, __('Non payment callback: IGNORING'));
        }

        $paymentId = $this->getPaymentId($data);

        $paymentInfo = $mercadoPago->get_payment_info($paymentId);
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

    public function getPaymentAmount()
    {
        return data_get($this->payment, 'request.preference.items.0.unit_price');
    }

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
