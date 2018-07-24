<?php

namespace App\Http\Controllers\Order;

use App\Http\Traits\CurrentUserOrder;
use App\Order;
use App\Product;
use App\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait ShoppingCart
{
    use CurrentUserOrder;

    /**
     * An alias for the show() method for the current logged in user cart.
     */
    public function getShoppingCart(Request $request)
    {
        return $this->show($request, $this->currentUserOrder());
    }

    /**
     * Set an order that is in payment, back to shopping cart.
     * Delete current shopping cart if any.
     */
    protected function backToShoppingCart($orderId)
    {
        $order = Order::where('status', Order::STATUS_PAYMENT)->find($orderId);
        if (!$order) {
            throw ValidationException::withMessages([
                'order_id' => [__('validation.available', ['attribute' => 'order_id'])],
            ]);
        }
        DB::transaction(function () use ($order) {
            $order->status = Order::STATUS_SHOPPING_CART;
            $order->save();
            foreach ($order->payments as $payment) {
                $payment->status = Sale::STATUS_CANCELED;
                $payment->save();
            }
            foreach ($order->sales as $sale) {
                $sale->status = Sale::STATUS_SHOPPING_CART;
                $sale->save();
            }
            foreach ($order->products as $product) {
                $product->status = Product::STATUS_AVAILABLE;
                $product->save();
            }
        });

        return $order->fresh();
    }

    /**
     * An alias for the update() method for the current logged in user.
     */
    public function updateShoppingCart(Request $request)
    {
        $orderId = $request->order_id;
        $order = null;
        if ($orderId) {
            $order = $this->backToShoppingCart($orderId);
        }

        if (!$order) {
            $order = $this->currentUserOrder();
        }

        return $this->update($request, $order);
    }

    /**
     * Get a Sale model for the given seller and order.
     * Create a new one if one does not exist.
     */
    protected function getSale($order, $sellerId)
    {
        $sale = $order->sales->firstWhere('user_id', $sellerId);
        if (!$sale) {
            $sale = new Sale();
            $sale->user_id = $sellerId;
            $sale->order_id = $order->id;

            $sale->status = Sale::STATUS_SHOPPING_CART;
            $sale->save();
        }
        return $sale;
    }

    /**
     * Get the products and group them by the user_id..
     */
    protected function getProductsByUser($productIds)
    {
        return Product::whereIn('id', $productIds)
            ->whereBetween('status', [Product::STATUS_APPROVED, Product::STATUS_AVAILABLE])
            ->get()->groupBy('user_id');
    }

    /**
     * Add products to the given cart/Order.
     */
    protected function addProducts(Request $request, Model $order)
    {
        $addProductIds = $request->add_product_ids;
        if (!$addProductIds) {
            return;
        }

        foreach ($this->getProductsByUser($addProductIds) as $userId => $products) {
            $sale = $this->getSale($order, $userId);
            $sale->products()->syncWithoutDetaching($products->pluck('id'));
        }

        return $order;
    }

    /**
     * Remove products from the given cart/Order.
     */
    protected function removeProducts(Request $request, Model $order)
    {
        $removeProductIds = $request->remove_product_ids;
        if (!$removeProductIds) {
            return;
        }

        foreach ($order->sales as $sale) {
            $sale->products()->detach($removeProductIds);
            $sale->load('products');
            if (!count($sale->products)) {
                $sale->delete();
            }
        }

        return $order;
    }
}
