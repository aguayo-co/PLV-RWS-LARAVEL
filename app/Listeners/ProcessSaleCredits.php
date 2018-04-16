<?php

namespace App\Listeners;

use App\CreditsTransaction;
use App\Events\SaleSaved;
use App\Payment;
use App\Sale;

class ProcessSaleCredits
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
     * @param  SaleSaved  $event
     * @return void
     */
    public function handle(SaleSaved $event)
    {
        $sale = $event->sale;

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
