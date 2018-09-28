<?php

namespace App\Listeners;

use App\CreditsTransaction;
use App\Order;
use App\Payment;
use App\Product;
use App\Sale;
use Illuminate\Support\Facades\DB;

class UnfreezeOrder
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
     * @param  $event
     * @return void
     */
    public function handle($event)
    {
        $order = $event->order;

        DB::transaction(function () use ($order) {
            $this->cancelPayments($order->payments);
            $this->unfreezeOrder($order);
            $this->unfreezeSales($order->sales);
        });
    }

    protected function cancelPayments($payments)
    {
        foreach ($payments as $payment) {
            $payment->status = Sale::STATUS_CANCELED;
            $payment->save();
        }
    }

    protected function unfreezeOrder($order)
    {
        $order->applied_coupon = null;
        $order->status = Order::STATUS_SHOPPING_CART;
        $order->save();
    }

    protected function unfreezeSales($sales)
    {
        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_SHOPPING_CART;

            $shipmentDetails = $sale->shipment_details;
            unset($shipmentDetails['cost']);
            unset($shipmentDetails['address_from']);
            $sale->shipment_details = $shipmentDetails;

            $sale->save();
            $this->unfreezeProducts($sale);
        }
    }

    protected function unfreezeProducts($sale)
    {
        // Check that the products are still on STATUS_PAYMENT,
        // if it is not, then do not change status.
        // This might happen due tu a race-condition when freezing the
        // order. We should respect the new status.
        $freshProducts = Product::find($sale->products->pluck('id'));
        foreach ($sale->products as $product) {
            $sale->products()->updateExistingPivot($product->id, [
                'price' => null,
            ]);
            $freshProduct = $freshProducts->firstWhere('id', $product->id);
            if ($freshProduct->status === Product::STATUS_PAYMENT) {
                $product->status = Product::STATUS_AVAILABLE;
            }
            $product->save();
        }
    }
}
