<?php

namespace App\Listeners;

use App\Events\SaleReturnSaved;
use App\Sale;
use App\SaleReturn;

class ProcessSaleStatusFromReturn
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
        $sale = $saleReturn->sale;
        if ($sale && $saleReturn->status === 0 && $saleReturn->wasRecentlyCreated) {
            if ($sale->status === Sale::STATUS_CANCELED) {
                return;
            }

            if ($sale->status <= Sale::STATUS_PAYED) {
                return;
            }

            if (!$sale->returned_products_ids->count()) {
                return;
            }

            if ($sale->products_ids == $sale->returned_products_ids) {
                $sale->status = Sale::STATUS_COMPLETED_RETURNED;
                $sale->save();
                return;
            }

            $sale->status = Sale::STATUS_COMPLETED_PARTIAL;
            $sale->save();
        }
    }
}
