<?php

namespace App\Listeners;

use App\CreditsTransaction;
use App\Events\SaleReturnSaved;
use App\Sale;
use App\SaleReturn;

class ProcessSaleReturnCredits
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
     * @param  SaleReturnSaved  $event
     * @return void
     */
    public function handle(SaleReturnSaved $event)
    {
        $saleReturn = $event->saleReturn;

        $changedStatus = array_get($saleReturn->getChanges(), 'status');

        switch ($changedStatus) {
            case SaleReturn::STATUS_CANCELED:
                $this->giveCreditsToSeller($saleReturn->sales->first());
                break;

            case SaleReturn::STATUS_COMPLETED:
                $this->giveCreditsBackToBuyer($saleReturn->sales->first());
                break;
        }
    }

    protected function giveCreditsBackToBuyer(Sale $sale)
    {
        CreditsTransaction::create([
            'user_id' => $sale->order->user_id,
            'amount' => $sale->returned_total,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Return was completed.']
        ]);
    }

    protected function giveCreditsToSeller(Sale $sale)
    {
        CreditsTransaction::create([
            'user_id' => $sale->user_id,
            'amount' => $sale->returned_total - $sale->returned_commission,
            'sale_id' => $sale->id,
            'extra' => ['reason' => 'Return was canceled.']
        ]);
    }
}
