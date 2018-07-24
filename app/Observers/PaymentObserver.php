<?php

namespace App\Observers;

use App\Payment;

class PaymentObserver
{
    /**
      * Listen to the Payment creating event.
      *
      * @param  \App\Payment  $oayment
      * @return void
      */
    public function creating(Payment $payment)
    {
        $order = $payment->order;

        // An order should only have one non canceled payment.
        // When a new payment is created, cancel any other previous payment.
        foreach ($order->payments as $payment) {
            $payment->status = Payment::STATUS_CANCELED;
            $payment->save();
        }
    }
}
