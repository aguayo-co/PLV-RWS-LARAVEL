<?php

namespace App\Traits;

use App\Product;
use App\Sale;

/**
 * All the logic for price calculation in products should be handled here.
 */
trait ProductPrice
{
    /**
     * Products can be sold for a lower price than
     * the originally set due to promotions or discounts.
     *
     * This attribute should show the price for which the
     * product is being sold.
     *
     * Once it has been sold, it should not be calculated, but
     * taken from the order which it was sold with.
     */
    protected function getSalePriceAttribute()
    {
        // If the product is loaded form a sale
        // which has a price in its pivot table, us that price.
        if (data_get($this, 'pivot.price')) {
            return $this->pivot->price;
        }

        // If product is set as sold, and we didn't have a pivot table
        // then get salePrice form the sale it was sold with.
        if ($this->status >= Product::STATUS_PAYMENT) {
            return $this->getSalePriceFromSale();
        }

        // If we got here, then calculate current price.
        $discount = $this->calculateDiscount();
        return $this->price - $discount;
    }

    protected function getSalePriceFromSale()
    {
        // If it was loaded by itself, get the sale that it was sold with.
        $sale = $this->sales()
            ->whereBetween('status', [Sale::STATUS_PAYMENT, SAle::STATUS_COMPLETED_PARTIAL])
            ->first();

        // It might happen that there is no sale.
        // It might have been cancelled.
        // If that happens, use the original published price.
        // This should be rare, and only happen if an admin manually
        // marked a sale as cancelled.
        return data_get($sale, 'pivot.price', $this->price);
    }

    /**
     * Discounts are calculated based on the groups the owner of the product
     * belongs to.
     *
     * The highest available discount should be considered.
     */
    protected function calculateDiscount()
    {
        $discountValue = $this->user->groups->max('discount_value');
        if (!$discountValue) {
            return 0;
        }

        return round($this->price * $discountValue / 100);
    }
}
