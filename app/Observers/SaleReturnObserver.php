<?php

namespace App\Observers;

use App\CreditsTransaction;
use App\Notifications\ProductReturn;
use App\Notifications\ProductReturned;
use App\Notifications\ReturnCreated;
use App\Notifications\ReturnSent;
use App\Notifications\ReturnSentAgreement;
use App\Sale;
use App\SaleReturn;

class SaleReturnObserver
{
    protected $saleReturn;

    /**
     * Listen to the SaleReturn saved event.
     *
     * @param  \App\SaleReturn  $saleReturn
     * @return void
     */
    public function saved(SaleReturn $saleReturn)
    {
        $this->saleReturn = $saleReturn;

        $changedStatus = array_get($saleReturn->getChanges(), 'status');

        switch ($changedStatus) {
            case SaleReturn::STATUS_CANCELED:
                $this->giveCreditsToSeller();
                break;

            case SaleReturn::STATUS_SHIPPED:
                $this->sendShippedNotifications();
                break;

            case SaleReturn::STATUS_DELIVERED:
                $this->sendDeliveredNotifications();
                break;

            case SaleReturn::STATUS_COMPLETED:
                $this->sendReceivedNotifications();
                $this->giveCreditsBackToBuyer();
                break;
        }
    }

    /**
     * Listen to the SaleReturn created event.
     *
     * @param  \App\SaleReturn  $saleReturn
     * @return void
     */
    public function created(SaleReturn $saleReturn)
    {
        $this->saleReturn = $saleReturn;

        $sale = $saleReturn->sale;

        if ($sale->products_ids == $sale->returned_products_ids) {
            $sale->status = Sale::STATUS_COMPLETED_RETURNED;
            $sale->save();
            return;
        }

        $sale->status = Sale::STATUS_COMPLETED_PARTIAL;
        $sale->save();

        $this->sendCreatedNotifications();
    }

    protected function giveCreditsBackToBuyer()
    {
        $sale = $this->saleReturn->sales->first();

        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->returned_total - $sale->returned_discount,
            'sale_id' => $sale->id,
            'extra' => ['reason' => __('prilov.credits.reasons.returnCompleted')]
        ]);
    }

    protected function giveCreditsToSeller()
    {
        $sale = $this->saleReturn->sales->first();

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->returned_total - $sale->returned_commission,
            'commission' => $sale->returned_commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => __('prilov.credits.reasons.returnCanceled')]
        ]);
    }

    protected function sendCreatedNotifications()
    {
        $sale = $this->saleReturn->sales->first();
        $sale->order->user->notify(new ProductReturn(['sale_return' => $this->saleReturn]));
        $sale->user->notify(new ReturnCreated(['sale_return' => $this->saleReturn]));
    }

    protected function sendShippedNotifications()
    {
        $sale = $this->saleReturn->sales->first();
        $sale->user->notify(new ReturnSent(['sale_return' => $this->saleReturn]));
    }

    protected function sendDeliveredNotifications()
    {
        $sale = $this->saleReturn->sales->first();
        $sale->user->notify(new ReturnSentAgreement(['sale_return' => $this->saleReturn]));
    }

    protected function sendReceivedNotifications()
    {
        $sale = $this->saleReturn->sales->first();
        $sale->order->user->notify(new ProductReturned(['sale_return' => $this->saleReturn]));
    }
}
