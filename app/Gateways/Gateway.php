<?php

namespace App\Gateways;

use Illuminate\Http\Response;
use App\Payment;

class Gateway
{
    protected $gateway;
    protected const BASE_REF = "_PRILOV_LV-";

    public function __construct($gateway)
    {
        $gatewayClass = __NAMESPACE__ . '\\' . studly_case($gateway);
        if (!class_exists($gatewayClass)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment gateway not available.');
        }
        $this->gateway = new $gatewayClass;
    }

    /**
     * Return the name of the class used for this payment.
     */
    public function getName()
    {
        return class_basename($this->gateway);
    }

    /**
     * Generate a new Payment request.
     */
    public function paymentRequest(Payment $payment, $data)
    {
        $data['reference'] = $this->getReference($payment);
        return ($this->gateway)->getPaymentRequest($payment, $data);
    }

    /**
     * Generate a unique payment reference.
     */
    protected function getReference($payment)
    {
        return  $payment->uniqid . self::BASE_REF . $payment->id;
    }

    protected function getPaymentFromReference($reference)
    {
        $array = explode('-', $reference);
        $payment_id = trim(end($array));
        $payment = Payment::where('id', $payment_id)->first();
        if ($payment && $reference === $this->getReference($payment)) {
            return $payment;
        }
        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Payment reference not found.');
    }

    /**
     * Process a callback data.
     * Updates the Payment information.
     */
    public function processCallback($data)
    {
        $gateway = $this->gateway;
        $gateway->validateCallbackData($data);
        $gateway->setCallback($data);

        $payment = $this->getPaymentFromReference($gateway->getReference());
        $payment->status = $gateway->getStatus();

        $attempts = $payment->attempts;
        $attempts[] = $gateway->getData();

        $payment->attempts = $attempts;
        $payment->save();

        return $payment;
    }
}
