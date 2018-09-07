<?php

namespace App\Http\Controllers\Product;

use App\Message;
use App\Participant;
use App\Product;
use App\Sale;
use App\Thread;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ProductDelete
{
    // Deletes models associated with the given products
    // that should be deleted before deleting the products.
    // Does not deletes the products.
    protected function productsCleanup($products)
    {
        // Threads
        $productsIds = $products->pluck('id');
        $threads = Thread::whereIn('product_id', $productsIds)->withTrashed();

        $threadsIds = $threads->pluck('id');
        Participant::whereIn('thread_id', $threadsIds)->withTrashed()->forceDelete();
        Message::whereIn('thread_id', $threadsIds)->withTrashed()->forceDelete();
        $threads->forceDelete();

        // Sales
        foreach ($products as $product) {
            $salesIdsToDelete = $product->sales->filter(function ($sale) {
                return $sale->products->count() === 1;
            })->pluck('id');
            $product->sales()->sync([]);
            Sale::whereIn('id', $salesIdsToDelete)->forceDelete();
        }
    }

    protected function productsDelete($products)
    {
        $this->productsCleanup($products);
        Product::whereIn('id', $products->pluck('id'))->forceDelete();
    }
}
