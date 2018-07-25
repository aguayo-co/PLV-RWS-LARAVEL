<?php

namespace App\Gateways;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Response;
use App\Payment;

class PayU implements PaymentGateway
{
    use AutoNotificationsTrait;

    protected $callbackData;
    protected $payment;

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

    protected function getAmount()
    {
        return (int) ($this->payment->total * (100 + config('prilov.payments.percentage_fee.pay_u')) / 100);
    }

    protected function getRequestSignature($reference)
    {
        $apiKey = $this->getApiKey();
        $merchantId = $this->getMerchantId();
        $currency = $this->getCurrency();
        return md5("{$apiKey}~{$merchantId}~{$reference}~{$this->getAmount()}~{$currency}");
    }

    protected function getCallbackSignature($data)
    {
        $apiKey = $this->getApiKey();
        $merchantId = array_get($data, 'merchant_id', array_get($data, 'merchantId'));
        $currency = array_get($data, 'currency');
        $referenceSale = array_get($data, 'reference_sale', array_get($data, 'referenceCode'));
        $total = preg_replace('/(\.[0-9])0$/', '$1', array_get($data, 'value', array_get($data, 'TX_VALUE')));
        $statePol = array_get($data, 'state_pol', array_get($data, 'polTransactionState'));
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

    public function getPaymentRequest($data)
    {
        $buyer = $this->payment->order->user;
        return [
            'public_data' => [
                'test' => $this->testMode(),

                'accountId' => $this->getAccountId(),
                'merchantId' => $this->getMerchantId(),
                'referenceCode' => $data['reference'],
                'amount' => $this->getAmount(),
                'currency' => 'CLP',
                'signature' => $this->getRequestSignature($data['reference']),
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
        if ($this->getCallbackSignature($data) != array_get($data, 'sign', array_get($data, 'signature'))) {
            abort(Response::HTTP_BAD_REQUEST);
        }
    }

    public function setCallback($data)
    {
        $this->callbackData = $data;
    }

    public function getStatus()
    {
        $statePol = array_get($this->callbackData, 'state_pol', array_get($this->callbackData, 'polTransactionState'));
        return $this->getPaymentStatus($statePol);
    }

    public function getReference()
    {
        return array_get($this->callbackData, 'reference_sale', array_get($this->callbackData, 'referenceCode'));
    }

    public function getData()
    {
        return $this->callbackData;
    }

    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }
}
