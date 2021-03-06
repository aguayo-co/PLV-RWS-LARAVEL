<?php

namespace App\Observers;

use App\CreditsTransaction;
use App\Notifications\Accepted;
use App\Notifications\ConfirmedAgreement;
use App\Notifications\ConfirmedChilexpress;
use App\Notifications\ProductDelivered;
use App\Notifications\ProductDeliveredSent;
use App\Notifications\ProductReceivedChilexpress;
use App\Notifications\ProductReturnedCancelAgreement;
use App\Notifications\ProductReturnedCancelChilexpress;
use App\Notifications\ProductSent;
use App\Notifications\ProductSentChilexpress;
use App\Notifications\ReceivedAgreement;
use App\Notifications\ReturnedCanceled;
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

        // Check and remove Chilexpress as shipping method if not allowed.
        // Remove only if explicitly disallowed.
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
            case Sale::STATUS_PAYED:
                $this->sendPayedNotifications();
                break;

            case Sale::STATUS_SHIPPED:
                $this->sendShippedNotifications();
                break;

            case Sale::STATUS_DELIVERED:
                $this->sendDeliveredNotifications();
                break;

            case Sale::STATUS_RECEIVED:
                $this->sendReceivedNotifications();
                break;

            case Sale::STATUS_COMPLETED:
                $this->giveCreditsToSeller();
                $this->sendCompletedNotifications();
                break;

            case Sale::STATUS_COMPLETED_PARTIAL:
                $this->givePartialCreditsToSeller();
                break;

            case Sale::STATUS_CANCELED:
                $this->sendCanceledNotifications();
                $this->giveCreditsBackToBuyer();
                break;
        }
    }

    protected function sendPayedNotifications()
    {
        $sale = $this->sale;

        if ($sale->is_chilexpress) {
            $sale->user->notify(new ConfirmedChilexpress(['sale' => $sale]));
            return;
        }
        $sale->user->notify(new ConfirmedAgreement(['sale' => $sale]));
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
        if (array_has($sale->shipment_details, ['tracking_codes'])) {
            $sale->order->user->notify(new ProductDeliveredSent(['sale' => $sale]));
            return;
        }

        $sale->order->user->notify(new ProductDelivered(['sale' => $sale]));
    }

    protected function sendCompletedNotifications()
    {
        $sale = $this->sale;
        $sale->user->notify(new Accepted(['sale' => $sale]));
    }

    protected function giveCreditsBackToBuyer()
    {
        $sale = $this->sale;

        // Only return credits if payment was successful.
        if (!$sale->order->active_payment || !$sale->order->active_payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->total - $sale->coupon_discount + $sale->shipping_cost,
            'sale_id' => $sale->id,
            'extra' => ['reason' => __('prilov.credits.reasons.saleCanceled')]
        ]);
    }

    protected function sendReceivedNotifications()
    {
        $sale = $this->sale;
        $sale->user->notify(new ReceivedAgreement(['sale' => $sale]));
    }

    protected function sendCanceledNotifications()
    {
        $sale = $this->sale;

        if (!$sale->order->payment || !$sale->order->payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        $sale->user->notify(new ReturnedCanceled(['sale' => $sale]));

        if ($sale->is_chilexpress) {
            $sale->order->user->notify(new ProductReturnedCancelChilexpress(['sale' => $sale]));
            return;
        }

        $sale->order->user->notify(new ProductReturnedCancelAgreement(['sale' => $sale]));
    }

    protected function giveCreditsToSeller()
    {
        $sale = $this->sale;

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->total - ($sale->commission + $sale->coupon_discount),
            'commission' => $sale->commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => __('prilov.credits.reasons.orderCompleted')]
        ]);
    }

    protected function givePartialCreditsToSeller()
    {
        $sale = $this->sale;
        $returnedProductsIds = $sale->returned_products_ids->implode(', ');
        $reason = __('prilov.credits.reasons.orderPartial', ['products' => $returnedProductsIds]);
        $commission = $sale->commission - $sale->returned_commission;
        $amount = $sale->total - $sale->returned_total - ($commission + $sale->coupon_discount);

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $amount,
            'commission' => $commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => $reason]
        ]);
    }
}
