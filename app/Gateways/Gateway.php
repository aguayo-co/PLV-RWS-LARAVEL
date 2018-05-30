<?php

namespace App\Gateways;

use App\Order;
use App\Payment;
use Illuminate\Http\Response;

class Gateway
{
    protected $gateway;
    protected $order;
    protected const BASE_REF = "_PRILOV_LV-";

    public function __construct($gateway, Order $order)
    {
        $gatewayClass = __NAMESPACE__ . '\\' . studly_case($gateway);
        if (!class_exists($gatewayClass)) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Payment gateway not available.');
        }
        $this->gateway = new $gatewayClass($order);
        $this->order = $order;
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
        $paymentId = trim(end($array));
        $payment = Payment::where('id', $paymentId)->first();
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
