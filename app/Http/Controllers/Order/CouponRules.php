<?php

namespace App\Http\Controllers\Order;

use App\Coupon;

trait CouponRules
{
    protected function getCouponRules($order)
    {
        return [
            $this->getCouponActive($order),
            $this->getCouponIsFirstPurchase($order),
        ];
    }

    /**
     * Rule that validates that a coupon is active.
     */
    protected function getCouponActive($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $coupon = Coupon::where('code', $value)->first();
            if ($coupon->status === Coupon::STATUS_DISABLED) {
                return $fail(__('Cupón no habilitado.'));
            }

            if ($coupon->valid_from && now() < $coupon->valid_from) {
                return $fail(__('Cupón no ha iniciado.'));
            }

            if ($coupon->valid_to && $coupon->valid_to < now()) {
                return $fail(__('Cupón vencido.'));
            }
        };
    }

    /**
     * Rule that validates that a coupon is valid for first
     * purchase only.
     */
    protected function getCouponIsFirstPurchase($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $coupon = Coupon::where('code', $value)->first();
            if (!$coupon->first_purchase_only) {
                return;
            }

            if ($order->user->orders->count() === 1) {
                return;
            }

            return $fail(__('Cupón sólo permitido en primera compra.'));
        };
    }
}
