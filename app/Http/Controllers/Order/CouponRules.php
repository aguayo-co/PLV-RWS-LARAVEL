<?php

namespace App\Http\Controllers\Order;

use App\Coupon;
use App\Order;

trait CouponRules
{
    protected function getCouponRules($order)
    {
        return [
            $this->getCouponActive($order),
            $this->getCouponIsFirstPurchase($order),
            $this->getCouponIsApplicable($order),
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

            $userOrdersCount = $order->user->orders()
                ->where('status', '>=', Order::STATUS_PAYMENT)
                ->where('status', '<', Order::STATUS_CANCELED)
                ->count();
            if (!$userOrdersCount) {
                return;
            }

            return $fail(__('prilov.coupons.firstPurchaseOnly'));
        };
    }

    /**
     * Rule that validates that a coupon gives a discount.
     * If the discount value in the order is 0, the coupon does not apply to
     * any of the productos in the order. Reject.
     */
    protected function getCouponIsApplicable($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $coupon = Coupon::where('code', $value)->first();
            $testOrder = $order->fresh();
            $testOrder->coupon_id = $coupon->id;
            if ($testOrder->coupon_discount > 0) {
                return;
            }

            return $fail(__('prilov.coupons.notApplicable'));
        };
    }
}
