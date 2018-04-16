<?php

namespace App\Gateways;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Response;
use App\Payment;

class PayU implements PaymentGateway
{
    protected $callbackData;

    protected function getMerchantId()
    {
        return env('PAYU_MERCHANT_ID');
    }

    protected function getAccountId()
    {
        return env('PAYU_ACCOUNT_ID');
    }

    protected function getApiKey()
    {
        return env('PAYU_API_KEY');
    }

    protected function getCurrency()
    {
        return 'CLP';
    }

    protected function getRequestSignature(Payment $payment, $reference)
    {
        $apiKey = $this->getApiKey();
        $merchantId = $this->getMerchantId();
        $currency = $this->getCurrency();
        return md5("{$apiKey}~{$merchantId}~{$reference}~{$payment->total}~{$currency}");
    }

    protected function getCallbackSignature($data)
    {
        $apiKey = $this->getApiKey();
        $merchantId = array_get($data, 'merchant_id');
        $currency = array_get($data, 'currency');
        $referenceSale = array_get($data, 'reference_sale');
        $total = preg_replace('/(\.[0-9])0$/', '$1', array_get($data, 'value'));
        $statePol = array_get($data, 'state_pol');
        return md5("{$apiKey}~{$merchantId}~{$referenceSale}~{$total}~{$currency}~{$statePol}");
    }

    protected function testMode()
    {
        return App::environment('production') ? 0 : 1;
    }

    protected function getGatewayUrl()
    {
        if ($this->testMode()) {
            return 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/';
        }
        return 'https://checkout.payulatam.com/ppp-web-gateway-payu/';
    }

    public function getPaymentRequest(Payment $payment, $data)
    {
        $buyer = $payment->order->user;
        return [
            'public_data' => [
                'test' => $this->testMode(),

                'accountId' => $this->getAccountId(),
                'merchantId' => $this->getMerchantId(),
                'referenceCode' => $data['reference'],
                'amount' => $payment->total,
                'currency' => 'CLP',
                'signature' => $this->getRequestSignature($payment, $data['reference']),
                'description' => "Transacción Prilov",

                'confirmationUrl' => route('callback.gateway', ['gateway' => 'pay-u']),

                'buyerFullName' => $buyer->full_name,
                'buyerEmail' => $buyer->email,
                'gatewayUrl' => $this->getGatewayUrl(),
            ]
        ];
    }

    protected function getPaymentStatus($statePol)
    {
        switch ($statePol) {
            case 4:
                return Payment::STATUS_SUCCESS;
            default:
                return Payment::STATUS_ERROR;
        }
    }

    public function validateCallbackData($data)
    {
        if ($this->getCallbackSignature($data) != array_get($data, 'sign')) {
            abort(Response::HTTP_BAD_REQUEST);
        }
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
    }

    public function getStatus()
    {
        return $this->getPaymentStatus($this->callbackData['state_pol']);
    }

    public function getReference()
    {
        return $this->callbackData['reference_sale'];
    }

    public function getData()
    {
        return $this->callbackData;
    }
}
