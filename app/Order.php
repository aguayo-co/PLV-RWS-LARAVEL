<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;

class Order extends Model
{
    use HasStatuses;
    use HasStatusHistory;

    const STATUS_SHOPPING_CART = 10;
    const STATUS_PAYMENT = 20;
    const STATUS_PAYED = 30;
    const STATUS_CANCELED = 99;

    protected $fillable = ['shipping_information', 'coupon_id'];
    protected $with = ['sales', 'creditsTransactions', 'payments', 'coupon'];
    protected $appends = ['total', 'due', 'coupon_discount', 'used_credits'];

    /**
     * Get the user that buys this.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function coupon()
    {
        return $this->belongsTo('App\Coupon');
    }

    /**
     * Get the order payments.
     */
    public function payments()
    {
        return $this->hasMany('App\Payment');
    }

    /**
     * Get the order payments.
     */
    public function sales()
    {
        return $this->hasMany('App\Sale');
    }

    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    public function getUsedCreditsAttribute()
    {
        return -$this->creditsTransactions->sum('amount');
    }

    /**
     * Get the order products.
     */
    public function getProductsAttribute()
    {
        return $this->sales->pluck('products')->flatten();
    }

    /**
     * Get the order products that were marked for return.
     */
    public function getReturnedProductsAttribute()
    {
        return $this->sales->pluck('returned_products')->flatten();
    }

    /**
     * The total value of the order.
     */
    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }

    /**
     * The value the user needs to pay after applying the credits
     * and the coupons the user used.
     */
    public function getDueAttribute()
    {
        $total = $this->products->sum('price');
        $credited = $this->used_credits;
        $discount = $this->coupon_discount;
        return $total - $credited - $discount;
    }

    /**
     * Calculate and return the value of the discount
     * for the coupon added to the order.
     */
    public function getCouponDiscountAttribute()
    {
        $coupon = $this->coupon;
        if (!$coupon) {
            return 0;
        }

        $discountedProducts = $this->getDiscountedProducts();

        $discountValue = $coupon->discount_value;
        $productsTotal = $discountedProducts->sum('price');

        if ($coupon->discount_type === '%') {
            $discountValue = round($productsTotal * $coupon->discount_value / 100);
        }

        return min($discountValue, $productsTotal);
    }

    /**
     * Return the products from the order that meet the coupon criteria.
     */
    protected function getDiscountedProducts()
    {
        $products = $this->products;
        $coupon = $this->coupon;

        if (!$coupon) {
            return $products;
        }

        if ($coupon->brands_ids->isNotEmpty()) {
            $products = $products->whereIn('brand_id', $coupon->brands_ids->all());
        }

        if ($coupon->campaigns_ids->isNotEmpty()) {
            $products = $products->filter(function ($product) use ($coupon) {
                return $product->campaign_ids->intersect($coupon->campaigns_ids)->isNotEmpty();
            });
        }

        if ($minimumCommission = $coupon->minimum_commission) {
            $products = $products->filter(function ($product) use ($minimumCommission) {
                return $minimumCommission <= $product->commission;
            });
        }

        if ($minimumPrice = $coupon->minimum_price) {
            $products = $products->filter(function ($product) use ($minimumPrice) {
                return $minimumPrice <= $product->price;
            });
        }

        return $products;
    }

    public function getCouponCodeAttribute()
    {
        return $this->coupon ? $this->coupon->code : null;
    }

    /**
     * Shipping information comes as an object or array,
     * encode to json and store everything.
     */
    public function setShippingInformationAttribute($value)
    {
        $this->attributes['shipping_information'] = json_encode($value);
    }

    public function getShippingInformationAttribute($value)
    {
        return json_decode($value, true);
    }
}
