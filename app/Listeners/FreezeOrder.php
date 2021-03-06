<?php

namespace App\Listeners;

use App\CreditsTransaction;
use App\Events\PaymentStarted;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Support\Facades\DB;

class FreezeOrder
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
     * @param  PaymentStarted  $event
     * @return void
     */
    public function handle(PaymentStarted $event)
    {
        $order = $event->order;

        DB::transaction(function () use ($order) {
            $this->freezeOrder($order);
            $this->freezeSales($order->sales);
        });
    }

    protected function freezeOrder($order)
    {
        $order->applied_coupon = [
            'discount_per_product' => $order->discount_per_product,
            'discount' => $order->coupon_discount,
        ];
        $order->status = Order::STATUS_PAYMENT;
        $order->save();
    }

    protected function freezeSales($sales)
    {
        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_PAYMENT;

            $shipmentDetails = $sale->shipment_details;
            $shipmentDetails['cost'] = $sale->shipping_cost;
            $shipmentDetails['address_from'] = $sale->ship_from;
            $sale->shipment_details = $shipmentDetails;

            $sale->save();
            $this->freezeProducts($sale);
        }
    }

    protected function freezeProducts($sale)
    {
        foreach ($sale->products as $product) {
            $sale->products()->updateExistingPivot($product->id, [
                'price' => $product->sale_price,
            ]);
            $product->status = Product::STATUS_PAYMENT;
            $product->save();
        }
    }
}
