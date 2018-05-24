<?php

namespace App\Observers;

use App\CreditsTransaction;
use App\Payment;
use App\Sale;

class SaleObserver
{
    /**
     * Listen to the Sale saving event.
     *
     * @param  \App\Sale  $sale
     * @return void
     */
    public function saving(Sale $sale)
    {
        // When moving into STATUS_PAYMENT, set cost and address_from to be persisted.
        if ($sale->status === Sale::STATUS_PAYMENT && array_has($sale->getDirty(), 'status')) {
            $shipmentDetails = $sale->shipment_details;
            $shipmentDetails['cost'] = $sale->shipping_cost;
            $shipmentDetails['address_from'] = $sale->ship_from;
            $sale->shipment_details = $shipmentDetails;
        }

        // Check and remove Chilexpress as shipping method if not allowed.
        if ($sale->status === Sale::STATUS_SHOPPING_CART
            && $sale->isChilexpress()
            && !$sale->allow_chilexpress) {
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
        $changedStatus = array_get($sale->getChanges(), 'status');

        switch ($changedStatus) {
            case Sale::STATUS_CANCELED:
                if ($sale->order->payment && $sale->order->payment->status === Payment::STATUS_SUCCESS) {
                    $this->giveCreditsBackToBuyer($sale);
                }
                break;

            case Sale::STATUS_COMPLETED:
                $this->giveCreditsToSeller($sale);
                break;

            case Sale::STATUS_COMPLETED_PARTIAL:
                $this->givePartialCreditsToSeller($sale);
                break;
        }
    }

    protected function giveCreditsBackToBuyer(Sale $sale)
    {
        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->total,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Order was canceled.']
        ]);
    }

    protected function giveCreditsToSeller(Sale $sale)
    {
        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->total - $sale->commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Order was completed.']
        ]);
    }

    protected function givePartialCreditsToSeller(Sale $sale)
    {
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
