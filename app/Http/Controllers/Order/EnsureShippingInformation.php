<?php

namespace App\Http\Controllers\Order;

use App\Address;

trait EnsureShippingInformation
{
    /**
     * Ensures that an Order has shipping information.
     *
     * If the given order hs no address and phone, the user's
     * favorite address and profile phone will be assigned.
     */
    protected function ensureShippingInformation($order)
    {
        // Set address from user's favorite.
        if (!data_get($order, 'shipping_information.address') && $order->user->favorite_address_id) {
            $shippingInformation = $order->shipping_information;
            $shippingInformation['address'] = Address::where(
                'id',
                $order->user->favorite_address_id
            )->first()->toArray();
            $order->shipping_information = $shippingInformation;
        }

        // Set phone from user profile.
        if (!data_get($order, 'shipping_information.phone')) {
            $shippingInformation = $order->shipping_information;
            $shippingInformation['phone'] = $order->user->phone;
            $order->shipping_information = $shippingInformation;
        }
    }
}
