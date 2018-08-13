<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Product\ProductDelete;
use App\Order;
use App\Participant;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait UserDelete
{
    use ProductDelete;

    // Deletes models associated with the given users
    // that should be deleted before deleting the users.
    // Does not deletes the users.
    protected function usersCleanup($users)
    {
        // Force delete unsold products.
        $usersIds = $users->pluck('id');
        $productsToDelete = Product::whereIn('user_id', $usersIds)
            ->where('status', '<', Product::STATUS_PAYMENT)
            ->get();
        $this->productsDelete($productsToDelete);

        // Force delete pending orders.
        $ordersToDelete = Order::where('status', '<', Order::STATUS_PAYMENT)
            ->whereIn('user_id', $usersIds);
        $salesToDelete = Sale::whereIn('order_id', $ordersToDelete->pluck('id'));
        foreach ($salesToDelete->get() as $sale) {
            $sale->products()->sync([]);
        }
        $salesToDelete->forceDelete();
        $ordersToDelete->forceDelete();

        // Force delete pending sales.
        $salesToDelete = Sale::where('status', '<', Sale::STATUS_PAYMENT)
            ->whereIn('user_id', $usersIds);
        foreach ($salesToDelete->get() as $sale) {
            $sale->products()->sync([]);
        }
        $salesToDelete->forceDelete();

        // Remove from all Threads.
        Participant::whereIn('user_id', $usersIds)->delete();
    }
}
