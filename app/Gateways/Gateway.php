<?php

namespace App\Gateways;

use App\Events\PaymentSuccessful;
use App\Order;
use App\Payment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class Gateway
{
    public $gateway;
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
        // Do not change approved or canceled payments status.
        if (!in_array($payment->status, [Payment::STATUS_SUCCESS, Payment::STATUS_CANCELED])) {
            $payment->status = $gateway->getStatus();
        }
        $gateway->setPayment($payment);

        $attempts = $payment->attempts;
        $key = md5(json_encode($gateway->getData()));
        $attempts[$key] = $gateway->getData();

        $payment->attempts = $attempts;
        $statusChanged = array_get($payment->getDirty(), 'status');

        DB::transaction(function () use ($payment, $statusChanged) {
            $payment->save();
            if ($statusChanged === Payment::STATUS_SUCCESS) {
                event(new PaymentSuccessful($payment->order));
            }
        });

        switch ($statusChanged) {
            case Payment::STATUS_SUCCESS:
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
