<?php

namespace App\Observers;

use App\CreditsTransaction;
use App\Notifications\ProductDelivered;
use App\Notifications\ProductDeliveredSent;
use App\Notifications\ProductReceivedChilexpress;
use App\Notifications\ProductSent;
use App\Notifications\ProductSentChilexpress;
use App\Payment;
use App\Sale;

class SaleObserver
{
    protected $sale;

    /**
     * Listen to the Sale saving event.
     *
     * @param  \App\Sale  $sale
     * @return void
     */
    public function saving(Sale $sale)
    {
        $this->sale = $sale;

        // When moving into STATUS_PAYMENT, set cost and address_from to be persisted.
        if ($sale->status === Sale::STATUS_PAYMENT && array_has($sale->getDirty(), 'status')) {
            $shipmentDetails = $sale->shipment_details;
            $shipmentDetails['cost'] = $sale->shipping_cost;
            $shipmentDetails['address_from'] = $sale->ship_from;
            $sale->shipment_details = $shipmentDetails;
        }

        // Check and remove Chilexpress as shipping method if not allowed.
        if ($sale->status === Sale::STATUS_SHOPPING_CART
            && $sale->is_chilexpress
            && $sale->allow_chilexpress === false) {
                $sale->shipping_method_id = null;
        }
    }

    /**
     * Listen to the Sale saved event.
     *
     * @param  \App\Sale  $sale
     * @return void
     */
    public function saved(Sale $sale)
    {
        $this->sale = $sale;

        $changedStatus = array_get($sale->getChanges(), 'status');

        switch ($changedStatus) {
            case Sale::STATUS_SHIPPED:
                $this->sendShippedNotifications();
                break;

            case Sale::STATUS_DELIVERED:
                $this->sendDeliveredNotifications();
                break;

            case Sale::STATUS_RECEIVED:
                $this->sendReceivedNotifications();
                break;

            case Sale::STATUS_CANCELED:
                if ($sale->order->payment && $sale->order->payment->status === Payment::STATUS_SUCCESS) {
                    $this->giveCreditsBackToBuyer();
                }
                break;

            case Sale::STATUS_COMPLETED:
                $this->giveCreditsToSeller();
                break;

            case Sale::STATUS_COMPLETED_PARTIAL:
                $this->givePartialCreditsToSeller();
                break;
        }
    }

    protected function sendShippedNotifications()
    {
        $sale = $this->sale;

        if ($sale->is_chilexpress) {
            $sale->order->user->notify(new ProductSentChilexpress(['sale' => $sale]));
            return;
        }
        $sale->order->user->notify(new ProductSent(['sale' => $sale]));
    }

    protected function sendDeliveredNotifications()
    {
        $sale = $this->sale;

        if ($sale->is_chilexpress) {
            $sale->order->user->notify(new ProductReceivedChilexpress(['sale' => $sale]));
            return;
        }
        // If we have tracking codes, then it was sent and not personally delivered.
        if (data_has($sale, ['shipment_details', 'tracking_codes'])) {
            $sale->order->user->notify(new ProductDeliveredSent(['sale' => $sale]));
            return;
        }
        $sale->order->user->notify(new ProductDelivered(['sale' => $sale]));
    }

    protected function sendReceivedNotifications()
    {
        $sale = $this->sale;

        if ($sale->is_chilexpress) {
            return;
        }
    }

    protected function giveCreditsBackToBuyer()
    {
        $sale = $this->sale;

        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->total,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Order was canceled.']
        ]);
    }

    protected function giveCreditsToSeller()
    {
        $sale = $this->sale;

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->total - $sale->commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Order was completed.']
        ]);
    }

    protected function givePartialCreditsToSeller()
    {
        $sale = $this->sale;
        $returnedProductsIds = $sale->returnedProductsIds->implode(', ');
        $reason = 'Order was completed with products ":products" returned.';
        $amount = $sale->total - $sale->commission - $sale->returned_total;

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $amount,
            'sale_id' => $sale->id,
            'extra' => ['reason' => __($reason, ['products' => $returnedProductsIds])]
        ]);
    }
}
