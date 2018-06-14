<?php

namespace App;

use App\Traits\DateSerializeFormat;
use App\Traits\HasSingleFile;
use App\Traits\HasStatuses;
use App\Traits\HasStatusHistory;
use App\Traits\SaleChilexpress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Sale extends Model
{
    use HasStatuses;
    use HasStatusHistory;
    use HasSingleFile;
    use SaleChilexpress;
    use DateSerializeFormat;

    // Numbers are used to know when an action can be taken.
    // For instance, an order can not be marked as shipped if it
    // has not been payed: "status < PAYED".
    // But once it passes that stage, can be marked as shipped at any time.
    const STATUS_SHOPPING_CART = 10;
    const STATUS_PAYMENT = 20;
    const STATUS_PAYED = 30;
    const STATUS_SHIPPED = 40;
    const STATUS_DELIVERED = 41;
    const STATUS_RECEIVED = 49;
    const STATUS_COMPLETED = 90;
    const STATUS_COMPLETED_RETURNED = 91;
    const STATUS_COMPLETED_PARTIAL = 92;
    const STATUS_CANCELED = 99;

    protected $fillable = ['shipment_details', 'status'];
    protected $appends = ['shipping_label', 'shipping_cost'];

    /**
     * Get the user that sells this.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    /**
     * Get the sale products.
     */
    public function products()
    {
        return $this->belongsToMany('App\Product')->withPivot('sale_return_id');
    }

    public function getProductsIdsAttribute()
    {
        return $this->products->pluck('id');
    }

    /**
     * Get the sale products that were marked for return.
     */
    public function getReturnedProductsAttribute()
    {
        return $this->products->whereNotIn('pivot.sale_return_id', [null]);
    }

    public function getReturnedProductsIdsAttribute()
    {
        return $this->returned_products->pluck('id');
    }

    /**
     * The total value of the sale.
     */
    public function getTotalAttribute()
    {
        return $this->products->sum('price');
    }

    public function getDiscountPerProductAttribute()
    {
        return $this->order->discount_per_product->only($this->products_ids);
    }

    /**
     * The discount for this sale.
     */
    public function getCouponDiscountAttribute()
    {
        return $this->discount_per_product->sum('discount');
    }

    /**
     * The total value of the returned products.
     */
    public function getReturnedTotalAttribute()
    {
        return $this->products->whereIn('id', $this->returned_products_ids)->sum('price');
    }

    /**
     * The discount value of the returned products.
     */
    public function getReturnedDiscountAttribute()
    {
        return $this->discount_per_product->only($this->returned_products_ids)->sum('discount');
    }

    /**
     * The value off the commission for the sale.
     */
    public function getCommissionAttribute()
    {
        return $this->products->sum(function ($product) {
            return round($product->price * $product->commission / 100);
        });
    }

    /**
     * The cost of shipping this order via Chilexpress.
     */
    public function getShippingCostAttribute()
    {
        // If key exists already, return its value even if null.
        if (array_has($this->shipment_details, 'cost')) {
            return array_get($this->shipment_details, 'cost');
        }

        if (!$this->is_chilexpress) {
            return 0;
        }

        if (!$this->allow_chilexpress) {
            return 0;
        }

        $shipFrom = $this->ship_from;
        $shipTo = $this->ship_to;

        return $this->getChilexpressCost($shipFrom, $shipTo);
    }

    /**
     * Return a valid Address for Chilexpress usage.
     */
    public function getShipToAttribute()
    {
        $shippingAddress = data_get($this->order->shipping_information, 'address');
        if (!$shippingAddress) {
            return false;
        }
        if (!data_get($shippingAddress, 'geonameid')) {
            return false;
        }

        return new Address($shippingAddress);
    }

    /**
     * Return a valid Address for Chilexpress usage.
     */
    public function getShipFromAttribute()
    {
        // If key exists already, return its value even if null.
        if (array_has($this->shipment_details, 'address_from')) {
            $addressFrom = array_get($this->shipment_details, 'address_from');
            return $addressFrom ? new Address($addressFrom) : null;
        }

        return $this->user->addresses
            ->where('id', $this->user->favorite_address_id)->first();
    }

    public function getReturnedCommissionAttribute()
    {
        return $this->products->whereIn('id', $this->returned_products_ids)->sum(function ($product) {
            return round($product->price * $product->commission / 100);
        });
    }

    /**
     * This relation brings the SaleReturn that was created for this Sale.
     * It should only be one due to how they are created (SaleReturnController),
     * even though the relation is a belongsToMany().
     */
    public function returns()
    {
        return $this->belongsToMany('App\SaleReturn', 'product_sale');
    }

    public function creditsTransactions()
    {
        return $this->hasMany('App\CreditsTransaction');
    }

    public function shippingMethod()
    {
        return $this->belongsTo('App\ShippingMethod');
    }

    #                                 #
    # Start Shipment Details methods. #
    #                                 #
    public function setShipmentDetailsAttribute($value)
    {
        $this->attributes['shipment_details'] = json_encode($value);
    }

    public function getShipmentDetailsAttribute($value)
    {
        return json_decode($value, true) ?: [];
    }
    #                                 #
    # End Shipment Details methods.   #
    #                                 #

    #                                 #
    # Start Label image methods.      #
    #                                 #
    protected function getShippingLabelAttribute()
    {
        $url = $this->getFileUrl('shipping_label');
        if ($url) {
            return $url;
        };

        if ($this->status < Sale::STATUS_PAYED) {
            return;
        }

        try {
            $labelData = $this->generateChilexpressLabel();
        } catch (\SoapFault $e) {
            Log::warning('SOAP Chilexpress service failed.', ['error' => $e]);
            return 'Error';
        }

        if ($labelData === -1) {
            return 'Error';
        }

        if (!$labelData) {
            return null;
        }

        $this->setContentToFile('shipping_label', $labelData->imagenEtiqueta, 'jpeg');

        // Do not store image data in DB.
        unset($labelData->xmlSalidaEpl);
        unset($labelData->imagenEtiqueta);
        $shipmentDetails = $this->shipment_details;
        $shipmentDetails['label_data'] = $labelData;
        $this->shipment_details = $shipmentDetails;
        $this->save();

        return $this->getFileUrl('shipping_label');
    }
    #                                 #
    # End Label image methods.        #
    #                                 #
}
