<?php

namespace App\Observers;

use App\CreditsTransaction;
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

            case SaleReturn::STATUS_COMPLETED:
                $this->giveCreditsBackToBuyer();
                break;
        }
    }

    /**
     * Listen to the SaleReturn saved event.
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
    }

    protected function giveCreditsBackToBuyer()
    {
        $sale = $this->saleReturn->sales->first();

        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->returned_total,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Return was completed.']
        ]);
    }

    protected function giveCreditsToSeller()
    {
        $sale = $this->saleReturn->sales->first();

        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->returned_total - $sale->returned_commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Return was canceled.']
        ]);
    }
}
