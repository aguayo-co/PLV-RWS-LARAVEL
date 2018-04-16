<?php

namespace App\Http\Controllers\Order;

use App\Order;
use App\Payment;
use App\Sale;
use Illuminate\Support\Facades\DB;

trait OrderControllerRules
{
    /**
     * Given an attribute in dot notation, get the ID
     *
     * Example:
     * sales.10.status -> 10
     */
    protected function getIdFromAttribute($attribute)
    {
        $matches = [];
        $matched = preg_match('/.*?\.([0-9]+)(\..*)?$/', $attribute, $matches);
        return $matched ? $matches[1] : null;
    }

    /**
     * Given an attribute name form the data array, return the sale
     * it belongs to.
     *
     * Example:
     * sales.10.status -> $sale->id == 10
     */
    protected function getSaleFromAttribute($attribute, $order)
    {
        return $order->sales->firstWhere('id', $this->getIdFromAttribute($attribute));
    }

    /**
     * Rule that validates that the ids from the attributes are integers.
     */
    protected function getIdIsValidRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = $this->getIdFromAttribute($attribute);
            if (!$saleId) {
                return $fail(__('validation.integer'));
            }
        };
    }

    /**
     * Rule that validates that the given sale belongs to current order.
     */
    protected function getSaleIsValidRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $sale = $this->getSaleFromAttribute($attribute, $order);
            if (!$sale) {
                $validIds = $order->sales->pluck('id')->implode(', ');
                return $fail(__('validation.in', ['values' => $validIds]));
            }
        };
    }

    /**
     * Rule that validates that the given sale is still in a ShoppingCart.
     */
    protected function getSaleInShoppingCartRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $sale = $this->getSaleFromAttribute($attribute, $order);
            // If no Sale found, skip.
            // Sale validation done on a different Rule.
            if ($sale) {
                if ($sale->status > Sale::STATUS_SHOPPING_CART) {
                    return $fail(__('No se puede modificar Orden que no está en ShoppingCart.'));
                }
            }
        };
    }

    /**
     * Rule that validates that the given order is still in a ShoppingCart.
     */
    protected function getOrderInShoppingCartRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            // If no Order found, skip.
            if ($order && $order->status > Order::STATUS_SHOPPING_CART) {
                return $fail(__('No se puede modificar Orden que no está en ShoppingCart.'));
            }
        };
    }

    /**
     * Rule that validates that the given shipping method is
     * allowed by the owner of the sale item to which it is being assigned.
     */
    protected function getShippingMethodRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $sale = $this->getSaleFromAttribute($attribute, $order);
            // If no Sale found, skip.
            // Sale validation done on a different Rule.
            if ($sale) {
                $shippingMethodIds = DB::table('shipping_method_user')->where('user_id', $sale->user_id)
                    ->select('shipping_method_id')->pluck('shipping_method_id');
                if (!$shippingMethodIds->contains($value)) {
                    return $fail(__('validation.in', ['values' => $shippingMethodIds->implode(', ')]));
                }
            }
        };
    }

    /**
     * Rule that validates that a Sale status is valid.
     */
    protected function getStatusRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $saleId = preg_replace('/.*\.([0-9]+)\..*/', '$1', $attribute);
            $sale = $order->sales->firstWhere('id', $saleId);
            // Order needs to be payed.
            if ($sale->status < Sale::STATUS_PAYED) {
                return $fail(__('La orden no ha sido pagada.'));
            }
            // Do not go back in status.
            if ($value <= $sale->status) {
                return $fail(__('validation.min.numeric', ['min' => $sale->status + 1]));
            }
        };
    }

    /**
     * Rule that validates that an order has a Payment with gateway Transfer.
     */
    protected function paymentIsTransferRule($order)
    {
        return function ($attribute, $value, $fail) use ($order) {
            $payment = data_get($order, 'payments.0', null);
            if (!$payment) {
                return $fail(__('No Existe un pago en la orden.'));
            }
            if ($payment->gateway !== 'Transfer') {
                return $fail(__('El pago no es de tipo Transferencia.'));
            }
            if ($payment->status !== Payment::STATUS_PENDING && $payment->status !== Payment::STATUS_PROCESSING) {
                return $fail(__('El pago ya no acepta recibos.'));
            }
        };
    }
}
