<?php

namespace App\Listeners;

use App\CreditsTransaction;
use App\Events\PaymentSuccessful;
use App\Order;
use App\Payment;
use App\Sale;

class ApproveOrder
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PaymentSuccessful  $event
     * @return void
     */
    public function handle(PaymentSuccessful $event)
    {
        $order = $event->payment->order;

        if ($order->status === Order::STATUS_PAYED) {
            return;
        }

        switch (true) {
            case $order->sales->count() > 0:
                $this->approveShoppingCartOrder($order);
                break;

            case data_get($order->extra, 'product') === 'credits':
                $this->approveTransactionOrder($order);
                break;
        }

        $order->status = Order::STATUS_PAYED;
        $order->save();
    }

    protected function approveShoppingCartOrder($order)
    {
        // We want to fire events.
        foreach ($order->sales as $sale) {
            $sale->status = Sale::STATUS_PAYED;
            $sale->save();
        }
        foreach ($order->products as $product) {
            $product->status = Product::STATUS_SOLD;
            $product->save();
        }
    }

    protected function approveTransactionOrder($order)
    {
        $total = data_get($order->extra, 'total');
        CreditsTransaction::create([
            'user_id' => $order->user_id,
            'amount' => $total,
            'order_id' => $order->id,
            'extra' => ['reason' => 'Credits purchase.']
        ]);
    }
}