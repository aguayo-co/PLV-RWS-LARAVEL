<?php

namespace App\Http\Controllers\Order;

use App\Address;

trait EnsureShippingInformation
{

    protected function ensureShippingInformation($order)
    {
        // Calculate address from address_id.
        if (!data_get($order, 'shipping_information.address') && $order->user->favorite_address_id) {
            $shippingInformation = $order->shipping_information;
            $shippingInformation['address'] = Address::where(
                'id',
                $order->user->favorite_address_id
            )->first()->toArray();
            $order->shipping_information = $shippingInformation;
        }

        // Set phone to shipping information.
        if (!data_get($order, 'shipping_information.phone')) {
            $shippingInformation = $order->shipping_information;
            $shippingInformation['phone'] = $order->user->phone;
            $order->shipping_information = $shippingInformation;
        }
    }
}
