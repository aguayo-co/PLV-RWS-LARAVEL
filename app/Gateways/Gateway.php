<?php

namespace App\Gateways;

use App\Events\PaymentSuccessful;
use App\Order;
use App\Payment;
use Illuminate\Http\Response;

class Gateway
{
    protected $gateway;
    protected $order;
    protected const BASE_REF = "-_PRILOV_LV";

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
        ($this->gateway)->setPayment($payment);
        return ($this->gateway)->getPaymentRequest($data);
    }

    /**
     * Generate a unique payment reference.
     */
    protected function getReference($payment)
    {
        return  $payment->uniqid . self::BASE_REF;
    }

    protected function getPaymentFromReference($reference)
    {
        $array = explode('-', $reference);
        $uniqid = trim(array_shift($array));
        if (!$uniqid) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Payment reference not found.');
        }

        $payment = Payment::where('uniqid', $uniqid)->first();
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
        // DO not change approved payments status.
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            $payment->status = $gateway->getStatus();
        }
        $gateway->setPayment($payment);

        $attempts = $payment->attempts;
        $attempts[] = $gateway->getData();

        $payment->attempts = $attempts;
        $payment->save();

        $statusChanged = array_get($payment->getChanges(), 'status');

        switch ($statusChanged) {
            case Payment::STATUS_SUCCESS:
                event(new PaymentSuccessful($payment->order));
                if ($payment->order->status === Order::STATUS_PAYED) {
                    $gateway->sendApprovedNotification();
                }
                break;

            case Payment::STATUS_ERROR:
                $gateway->sendRejectedNotification();
                break;
        }

        return $payment;
    }
}
