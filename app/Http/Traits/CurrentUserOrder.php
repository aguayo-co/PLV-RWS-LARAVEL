<?php

namespace App\Http\Traits;

use App\Order;

trait CurrentUserOrder
{
    /**
     * Get an Order model for the current user based on the status provided.
     *
     * The oldest one should be used at any moment until that one is moved to
     * a different status.
     */
    public function currentUserOrder($status = Order::STATUS_SHOPPING_CART)
    {
        // Ensure we have a status between STATUS_SHOPPING_CART and STATUS_TRANSACTION.
        $status = max($status, Order::STATUS_SHOPPING_CART);
        $status = min($status, Order::STATUS_TRANSACTION);

        $user = auth()->user();
        $order = Order::where(['user_id' => $user->id, 'status' => $status])->first();
        if (!$order) {
            $order = new Order();
            $order->user_id = $user->id;
            $order->status = $status;
            $order->save();
        }
        return $order;
    }
}
