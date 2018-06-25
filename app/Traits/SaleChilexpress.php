<?php

namespace App\Traits;

use App\Sale;
use Illuminate\Support\Facades\Log;

trait SaleChilexpress
{

    /**
     * Return cost of shipping with Chilexpress.
     */
    protected function getChilexpressTarifa($shipFrom, $shipTo)
    {
        $chilexpress = app()->get('chilexpress');
        try {
            return $chilexpress->tarifar($shipFrom, $shipTo, 1.4, 10, 10, 10);
        } catch (\SoapFault $e) {
            Log::warning('SOAP Chilexpress service failed.', ['error' => $e]);
            return;
        }
    }

    /**
     * Return cost of shipping with Chilexpress.
     */
    protected function getChilexpressCost($shipFrom, $shipTo)
    {
        return array_get($this->getChilexpressTarifa($shipFrom, $shipTo), 'valor');
    }

    /**
     * Return service used for Chilexpress.
     */
    protected function getChilexpressService($shipFrom, $shipTo)
    {
        return array_get($this->getChilexpressTarifa($shipFrom, $shipTo), 'codServicio', env('CHILEXPRESS_CODSERVICIO'));
    }

    /**
     * Return true if this order should calculate a shipping cost.
     */
    public function getIsChilexpressAttribute()
    {
        $shippingMethodSlug = data_get($this->shippingMethod, 'slug');
        if (!$shippingMethodSlug) {
            return false;
        }
        if (strpos($shippingMethodSlug, 'chilexpress') === false) {
            return false;
        }

        return true;
    }

    /**
     * Checks if Chilexpress should be allowed to be used for this Sale.
     *
     * Returns:
     * - true if should be allowed.
     * - false if should not be allowed.
     * - null when not relevant or unsure.
     */
    public function getAllowChilexpressAttribute()
    {
        // When out of shopping cart, use is_chilexpress as reference.
        if ($this->status !== Sale::STATUS_SHOPPING_CART) {
            return $this->is_chilexpress;
        }

        $shipFrom = $this->ship_from;
        // If we don't know where it is being sent from, block chilexpress.
        if (!$shipFrom) {
            return false;
        }

        if (!$shipFrom->can_admit_chilexpress) {
            return false;
        }

        $shipTo = $this->ship_to;
        // If we still don't know the shipping address, give no answer.
        if (!$shipTo) {
            return;
        }

        if (!$shipTo->can_deliver_chilexpress) {
            return false;
        }

        return true;
    }

    protected function generateChilexpressLabel()
    {
        if (!$this->is_chilexpress) {
            return;
        }

        if (!$this->allow_chilexpress) {
            return;
        }

        $shipFrom = $this->ship_from;
        $shipTo = $this->ship_to;

        $order = $this->order;
        $ref = "{$order->id}-{$this->id}";

        $chilexpress = app()->get('chilexpress');
        $codigoServicio = $this->getChilexpressService($shipFrom, $shipTo);
        return $chilexpress->order($ref, $codigoServicio, $this->user, $order->user, $shipFrom, $shipTo, 0.5, 10, 10, 10);
    }
}
