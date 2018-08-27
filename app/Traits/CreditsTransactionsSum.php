<?php

namespace App\Traits;

use App\CreditsTransaction;
use App\Order;

trait CreditsTransactionsSum
{
    protected function setActiveCreditsTransactionsConditions($query)
    {
        return $query
            ->leftJoin('orders as tr_orders', 'tr_orders.id', '=', 'credits_transactions.order_id')
            ->where(function ($query) {
                $query->whereNull('credits_transactions.transfer_status')
                    ->orWhere('credits_transactions.transfer_status', '!=', CreditsTransaction::STATUS_REJECTED);
            })
            ->where(function ($query) {
                $query->whereNull('credits_transactions.order_id')
                    ->orWhere('tr_orders.status', '!=', Order::STATUS_SHOPPING_CART);
            });
    }
}
